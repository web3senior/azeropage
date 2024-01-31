<?php

class V1_Model extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    function command($o, $tbl, $data = false, $id = false)
    {
        switch ($o) {
            case "count":
                return $this->db->select("select count(`$tbl[1]`) as `total` from `$tbl[0]`;");
            case "fetch":
                return $this->db->select("select * from `$tbl[0]`");
            case "delete":
                return $this->db->delete("$tbl[0]", "`$tbl[1]`='$id'");
            case "insert":
                return $this->db->insert("$tbl[0]", $data);
            case "info":
                return $this->db->select("select * from `$tbl[0]` where `$tbl[1]`=:val", [':val' => $id]);
            case "update":
                return $this->db->update("$tbl[0]", $data, "`$tbl[1]`='{$id}'");
            default:
                die("O is unknown!");
        }
    }

    function dashboard($addr)
    {
        return $data = [
            'merchant' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `merchant` WHERE `wallet_addr`=:wallet_addr;', [':wallet_addr' => $addr])[0]['total'],
            'invoice' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `invoice` i INNER JOIN `merchant` m ON m.id = i.merchant_id  WHERE m.`wallet_addr`=:wallet_addr;', [':wallet_addr' => $addr])[0]['total'],
            'paid_invoice' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `invoice` i INNER JOIN `merchant` m ON m.id = i.merchant_id   WHERE m.`wallet_addr`=:wallet_addr AND i.`status`=:status;', [':wallet_addr' => $addr, ':status' => 1])[0]['total'],
            'pending_invoice' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `invoice` i INNER JOIN `merchant` m ON m.id = i.merchant_id   WHERE m.`wallet_addr`=:wallet_addr AND i.`status`=:status;', [':wallet_addr' => $addr, ':status' => 0])[0]['total'],
            // 'decline' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_request` WHERE `status`=:status;', [':status' => 4])[0]['total'],
            // 'suspend' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_request` WHERE `status`=:status;', [':status' => 5])[0]['total'],
            // 'request_total' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_request`',)[0]['total'],
            // 'proceedings_total' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_proceedings`',)[0]['total'],
            // 'today_request_total' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_request` where `dt` LIKE "%' . jdate("Y-m-d", time(), '', '', 'en') . '%"')[0]['total'],
            // 'commission_total' =>  $this->db->select('SELECT COUNT(*) as `total` FROM `p_commission`',)[0]['total'],
        ];
    }


    function page($addr)
    {
        return $this->db->select("
             select * from page where `wallet_addr`=:wallet_addr order by `id` desc
              ",[":wallet_addr" => $addr]);
    }
    function merchant($addr)
    {
        return $this->db->select("
             select * from merchant order by `id` desc
              ");
    }

    function invoice($addr)
    {
        return $this->db->select("
             select * from merchant m right join invoice i on m.id = i.merchant_id  order by m.`id` desc
              ");
    }


    function invoiceDetail($addr, $invoice_id)
    {
        return $this->db->select("
             select * from merchant m right join invoice i on m.id = i.merchant_id where   
             i.id=:invoice_id order by m.`id` desc
              ", [':invoice_id' => $invoice_id]);
    }

    function request($tbl, $data, $start = 0, $count = 10)
    {
        $q = '';
        $first = false;
        if (isset($data->id) && !empty($data->id)) {
            if ($first)
                $q .= " AND r.`id` = '" . $data->id . "'";
            else
                $q .= "WHERE r.`id` = '" . $data->id . "'";

            $first = true;
        }

        if (isset($data->fullname) && !empty($data->fullname)) {
            if ($first)
                $q .= " AND r.`fullname` like '%" . $data->fullname . "%'";
            else
                $q .= "WHERE r.`fullname` like '%" . $data->fullname . "%'";

            $first = true;
        }

        if (isset($data->status) && $data->status !== "") {
            if ($first)
                $q .= " AND r.`status` = '" . $data->status . "'";
            else
                $q .= "WHERE r.`status` = '" . $data->status . "'";

            $first = true;
        }

        if (isset($data->p_request_type_id) && !empty($data->p_request_type_id)) {
            if ($first)
                $q .= " AND r.`p_request_type_id` = '" . $data->p_request_type_id . "'";
            else
                $q .= "WHERE r.`p_request_type_id` = '" . $data->p_request_type_id . "'";

            $first = true;
        }

        if (isset($data->accepted_name) && !empty($data->accepted_name)) {
            if ($first)
                $q .= " AND r.`accepted_name` = '" . $data->accepted_name . "'";
            else
                $q .= "WHERE r.`accepted_name` = '" . $data->accepted_name . "'";

            $first = true;
        }

        if (isset($_GET['page']) && !empty($_GET['page']) && is_numeric($_GET['page'])) $start = --$_GET['page'] * $count;

        $r = [
            'list' => $this->db->select("
              SELECT
                 r.*,
                 rt.`name` as `request_type_name`
              FROM
                  `p_request` r
                  inner join `p_request_type` rt on
                  rt.`id` = r.p_request_type_id
              $q
              ORDER BY r.`id` DESC
              LIMIT $start, $count;"),
            'total' => $this->db->select("select count(`id`) as `total` from `p_request` r  $q;")[0]['total']
        ];

        return $r;
    }


    function updateRequestFormContent($tbl, $data, $id)
    {
        $data = [
            "form_content" => $data
        ];

        return $res = $this->command('update', $tbl, $data, $id);
    }


    function updateLayer($data, $id)
    {
        $res = $this->db->select("
              update `p_layer` set `p_request_type_id`=:type, name=:name
              where `id` in (" . $id . ")
              ", [':type' => $data->p_request_type_id, ':name' => $data->name]);

        return true;
    }


    function uploadRequestDoc($file, $id, $field)
    {
        return $this->db->update('p_request', [$field => $file], "id = '$id'");
    }

    function passageEdit($tbl)
    {
        return $this->db->select("
             select * from p_request where `id`=:id limit 1
              ", [':id' => $_GET['id']]);
    }



    function requestDetail($tbl, $id)
    {
        return [
            'request' => $this->db->select("
            SELECT
               r.*,
               rt.`name` as `request_type_name`             
            FROM
                `p_request` r
                inner join `p_request_type` rt on
                rt.`id` = r.p_request_type_id
          WHERE r.id = :id 
            ORDER BY r.`id` DESC
          LIMIT 1", [':id' => $id]),
            'request_commission' => $this->db->select("
          SELECT
             COUNT(*) as `total`             
          FROM
              `p_request_commission` rc
        WHERE `p_request_id` = :id 
        LIMIT 1", [':id' => $id])
        ];
    }
    function requestCommissionDetail($tbl, $id)
    {
        return  $this->db->select("
        SELECT

    c.fullname as `commission_fullname`,
    c.side as `commission_side`,
    c.signature as `commission_signature`
FROM
    `p_request_commission` rc
INNER join `p_request` r on
r.id = rc.p_request_id
INNER join `p_commission` c on
c.id = rc.p_commission_id
where rc.p_request_id = :id
order by c.priority asc
           ", [':id' => $id]);
    }
    function printData($tbl)
    {
        return  $this->db->select("SELECT * FROM `print_data`");
    }

    function requestDetailConfirmation($tbl, $ids, $token)
    {
        $commission =  $this->db->select("
            SELECT
               *
            FROM
                `p_commission` c
          WHERE c.token = :token 
            ORDER BY c.`id` DESC
          LIMIT 1", [':token' => $token]);

        $proceeding =  $this->db->select("
        SELECT
        *
    FROM
        `p_proceedings`
    WHERE
        JSON_CONTAINS(`request_list`, '$ids', '$')");

        return [
            'request' => $this->db->select("
            SELECT
            r.*,
            rc.dt as `sign_date`
        FROM
            `p_request` r
            left join `p_request_commission` rc
            on r.id = rc.p_request_id and rc.p_commission_id=:commission_id
           
        WHERE
            r.id IN($ids)
        ORDER BY
            r.`id`
        DESC", [':commission_id' => $commission[0]['id']]),
            'commission' => $commission,
            'proceeding' =>  $proceeding
        ];
    }

    function commission($tbl, $start = 0, $count = 10)
    {
        if (isset($_GET['page']) && !empty($_GET['page']) && is_numeric($_GET['page'])) $start = --$_GET['page'] * $count;

        $r = [
            'list' => $this->db->select("
              SELECT
                 *
              FROM
                  `p_commission` r
              ORDER BY r.`id` DESC
              LIMIT $start, $count;"),
            'total' => $this->db->select("select count(`id`) as `total` from `p_commission`;")[0]['total']
        ];
        return $r;
    }

    function category($tbl, $start = 0, $count = 10)
    {
        if (isset($_GET['page']) && !empty($_GET['page']) && is_numeric($_GET['page'])) $start = --$_GET['page'] * $count;

        $r = [
            'list' => $this->db->select("
              SELECT
                 c.*
              FROM
                  `p_category` c
              ORDER BY c.`id` DESC
              LIMIT $start, $count;"),
            'total' => $this->db->select("select count(`id`) as `total` from `p_category`;")[0]['total']
        ];
        return $r;
    }

    function requestTypeFilter($tbl)
    {
        return $this->db->select("
        select * from p_request_type where people_can='1'
        ");
    }


    function checkSignatureCount($request_id, $commission_id)
    {
        return $this->db->select("
            SELECT
                *
            FROM
                `p_request_commission` rc
      where p_request_id =:p_request_id and p_commission_id =:p_commission_id 
            ", [
            ':p_request_id' => $request_id,
            ':p_commission_id' => $commission_id
        ]);
    }


    function report($tbl)
    {
        return [
            'r1' => $this->db->select("
            SELECT
                rt.name,
                COUNT(rt.id) as totalRecord
            FROM
                `p_layer` l
                INNER join `p_request_type` rt on 
                l.p_request_type_id = rt.id
               
            group by rt.id")
        ];
    }



    function reportAllRequest($data)
    {


        $q = '';
        $first = false;
        if (isset($data->start_date) && !empty($data->start_date) && isset($data->end_date) && !empty($data->end_date)) {
            if ($first)
                $q .= " AND r.`dt` between '" . $data->start_date . "' and '" . $data->end_date . "'";
            else
                $q .= "WHERE r.`dt` between '" . $data->start_date . "' and '" . $data->end_date . "'";

            $first = true;
        }

        if (isset($data->request_type) && !empty($data->request_type)) {
            if ($first)
                $q .= " AND r.`p_request_type_id` IN (" . $data->request_type . ")";
            else
                $q .= "WHERE r.`p_request_type_id` IN (" . $data->request_type . ")";

            $first = true;
        }

        if (isset($data->category) && !empty($data->category)) {
            if ($first)
                $q .= " AND r.`category_id` IN (" . $data->category . ")";
            else
                $q .= "WHERE r.`category_id` IN (" . $data->category . ")";

            $first = true;
        }

        if (isset($data->status) && !empty($data->status)) {
            if ($first)
                $q .= " AND r.`status` IN (" . $data->status . ")";
            else
                $q .= "WHERE r.`status` IN (" . $data->status . ")";

            $first = true;
        }

        // echo $q;die;


        $result = $this->db->select("
SELECT
   r.*,
   rt.name as   `request_type_name`
FROM
    `p_request` r
    INNER join `p_request_type` rt on 
    r.p_request_type_id = rt.id
$q
    order by r.`dt`
");
        return  $result;
    }




    function requestSend($data)
    {
        $data = (object) $data;

        $this->db->insert('p_request', [
            'p_request_type_id' => $data->request_type_id,
            'fullname' => $data->fullname,
            'address' => $data->address,
            'address_location' => $data->address_location,
            'tel' => $data->tel,
            'title' => $data->title,
            'name' => $data->name,
            'layer' => $data->layer,
            'dt' => jdate('Y-m-d H:i:s', time(), '', '', 'en'),
            'suggestion_names' => $data->suggestion_names,
        ]);
        return $this->db->lastInsertId();
    }

    public function subscription($data)
    {
        $count = $this->command('count', ['subscription', 'id']);
        if (is_array($count) && $count[0]['total'] < 10) {
            $data = $this->db->select(
                "INSERT INTO `subscription`(`ip`, `push_subscription`) VALUES(:ip,:push_subscription)",
                [':ip' => $data['ip'], ':push_subscription' => $data['push_subscription']]
            );
            return $this->db->lastInsertId();
        }
        return '';
    }



    public function allLink($id)
    {
        $data = $this->db->select('SELECT * FROM `link` WHERE `status`=:status ORDER BY `priority` ASC', [':status' => 1]);
        if (is_array($data) && !empty($data))
            return $data;
        else
            return 0;
    }
}
