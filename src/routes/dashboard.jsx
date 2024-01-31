import { useEffect, useState } from 'react'
import { web3Enable, web3Accounts } from '@polkadot/extension-dapp'
import { getPage, updatePage, deletePage } from '../util/api'
import { useAuth } from '../contexts/AuthContext'
import styles from './Dashbaord.module.scss'

function App() {
  const [extensions, setExtensions] = useState()
  const [accounts, setAccounts] = useState()
  const [page, setPage] = useState()
  const [name, setName] = useState()
  const [url, setURL] = useState()
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
    console.log(page[0])
    updatePage(page[0], auth.accounts[0].address)
  }

  const handleDelete = (i) => {
    let links = JSON.parse(page[0].links)
    delete links[i]
    console.log(links, links.length)
    console.log(Object.assign(...page, { links: JSON.stringify(links) }))
  }

  const handleChangeLink = (e, i, field) => {
    let links = JSON.parse(page[0].links)
    links[i][field] = e.target.value

    console.log(Object.assign(...page, { links: JSON.stringify(links) }))
  }

  const handleAdd = () => {
    let links = JSON.parse(page[0].links) === null ? []:JSON.parse(page[0].links)

    links.push({ URL: url, name: name })
    console.log(Object.assign(...page, { links: JSON.stringify(links) }))
  }

  useEffect(() => {
    getPage(auth.accounts[0].address).then((result) => {
      console.log(result, JSON.parse(result[0].links))
      setPage(result)
    })
  }, [])

  return (
    <>
      <article className={styles.container}>
        <div>
          <input type="text" name="name" defaultValue={page && page.length > 0 && `${page[0].username}`} />
          <input type="text" name="name" defaultValue={page && page.length > 0 && page[0].name} />
          <input type="text" name="bio" defaultValue={page && page.length > 0 && page[0].bio} />
        </div>

        <div className="mt-20">
          <ul>
            {page &&
              page.length > 0 &&
              JSON.parse(page[0].links) !== null &&
              JSON.parse(page[0].links).map(({ name, url }, i) => (
                <li key={i}>
                  <div className="card">
                    <label htmlFor="">Name</label>
                    <input type="text" defaultValue={name} onChange={(e) => handleChangeLink(e, i, 'name')} />

                    <label>URL</label>
                    <input type="text" defaultValue={url} onChange={(e) => handleChangeLink(e, i, 'url')} />

                    <ul className="mt-10">
                      <li>
                        <button onClick={() => handleUpdate()}>Update</button>
                        <button onClick={() => handleDelete(i)}>Delete</button>
                      </li>
                    </ul>
                  </div>
                </li>
              ))}
          </ul>
        </div>

        <div className="card">
          <div className="card__body">
            <label htmlFor="">Name</label>
            <input type="text" onChange={(e) => setName(e.target.value)} />

            <label>URL</label>
            <input type="text" placeholder="https://example.com" onChange={(e) => setURL(e.target.value)} />

            <button onClick={() => handleAdd()}>Add</button>
          </div>
        </div>
      </article>
    </>
  )
}

export default App
