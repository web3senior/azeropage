import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { web3Enable, web3Accounts } from '@polkadot/extension-dapp'
import { getPage, updatePage, deletePage } from '../util/api'
import { useAuth } from '../contexts/AuthContext'
import styles from './Dashbaord.module.scss'
import toast from 'react-hot-toast'

function App() {
  const [extensions, setExtensions] = useState()
  const [accounts, setAccounts] = useState()
  const [page, setPage] = useState()
  const [name, setName] = useState('')
  const [url, setURL] = useState('')
  const auth = useAuth()

  const loadAccountsFromExtensions = async () => {
    // extension-dapp API: connect to extensions; returns list of injected extensions
    const injectedExtensions = await web3Enable('Azeropage')
    setExtensions(injectedExtensions)

    // extension-dapp API: get accounts from extensions filtered by name
    const accounts = await web3Accounts({ extensions: ['aleph-zero-signer'] })
    setAccounts(accounts)
  }

  const handleUpdate = () => {
    const t = toast.loading(`Updating`)
    updatePage(page[0], auth.wallet).then(() => {
      toast.dismiss(t)
      toast.success(`Updated`)
    })
  }

  const handleDelete = (i) => {
    const t = toast.loading(`Updating`)
    let links = JSON.parse(page[0].links)
    links.splice(i, 1)
    page[0].links = JSON.stringify(links)
    updatePage(page[0], auth.wallet).then(() => {
      toast.dismiss(t)
      toast.success(`Link has been deleted`)
    })
  }

  const handleChangeLink = (e, i, field) => {
    let links = JSON.parse(page[0].links)
    links[i][field] = e.target.value
    page[0].links = JSON.stringify(links)
  }

  const handleAdd = () => {
    const t = toast.loading(`Adding`)

    let links = JSON.parse(page[0].links)
    links.push({ name: name, url: url })

    page[0].links = JSON.stringify(links)

    console.log(page)
    updatePage(page[0], auth.wallet).then(() => {
      toast.dismiss(t)
      toast.success(`New link has been added`)
    })
  }

  useEffect(() => {
    getPage(auth.wallet).then((result) => {
      console.log(result, JSON.parse(result[0].links))
      setPage(result)
    })
  }, [])

  return (
    <>
      <article className={`${styles.container} __container`} data-width={'medium'}>
        <div className="card">
          <div className="card__body">
            <label htmlFor="">Username</label>
            <input
              type="text"
              name="name"
              onChange={(e) => {
                page[0].username = e.target.value
              }}
              defaultValue={page && page.length > 0 && `${page[0].username}`}
            />

            <label htmlFor="">Fullname</label>
            <input
              type="text"
              name="name"
              onChange={(e) => {
                page[0].name = e.target.value
              }}
              defaultValue={page && page.length > 0 && page[0].name}
            />

            <label htmlFor="">Bio</label>
            <input
              type="text"
              name="bio"
              onChange={(e) => {
                page[0].bio = e.target.value
              }}
              defaultValue={page && page.length > 0 && page[0].bio}
            />

            <button className="btn mr-10" onClick={() => handleUpdate()}>
              Update
            </button>

            <Link to={`/${page && page.length > 0 && page[0].username}`}  className='text-danger' target='_blank'>Show my page</Link>
          </div>
        </div>

        <div className="mt-20">
          <ul>
            {page &&
              page.length > 0 &&
              JSON.parse(page[0].links) !== null &&
              JSON.parse(page[0].links).map(({ name, url }, i) => (
                <li key={i} className="mt-20">
                  <div className="card">
                    <label htmlFor="">Name</label>
                    <input type="text" defaultValue={name} onChange={(e) => handleChangeLink(e, i, 'name')} />

                    <label>URL</label>
                    <input type="text" defaultValue={url} onChange={(e) => handleChangeLink(e, i, 'url')} />

                    <ul className="mt-10">
                      <li>
                        <button className="btn" onClick={() => handleUpdate()}>
                          Update
                        </button>
                        <button className="btn ml-10" onClick={() => handleDelete(i)}>
                          Delete
                        </button>
                      </li>
                    </ul>
                  </div>
                </li>
              ))}
          </ul>
        </div>

        <div className="card mt-20" style={{background:'rgba(2,2,2,.1)'}}>
          <div className='card__header'>New Link</div>
          <div className="card__body">
            <label htmlFor="">Name</label>
            <input type="text" onChange={(e) => setName(e.target.value)} />

            <label>URL</label>
            <input type="text" placeholder="https://example.com" onChange={(e) => setURL(e.target.value)} />

            <button className="btn mt-20" onClick={() => handleAdd()}>
              Add
            </button>
          </div>
        </div>
      </article>
    </>
  )
}

export default App
