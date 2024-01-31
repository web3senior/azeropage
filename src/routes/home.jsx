import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Title } from './helper/DocumentTitle'
import Loading from './components/LoadingSpinner'
import { CheckIcon, ChromeIcon, BraveIcon } from './components/icons'
import toast, { Toaster } from 'react-hot-toast'
import Logo from './../../src/assets/logo.svg'
import Hero from './../../src/assets/hero.png'
import styles from './Home.module.scss'
import { useAuth } from './../contexts/AuthContext'

function Home({ title }) {
  Title(title)
  const [isLoading, setIsLoading] = useState(false)
  const auth = useAuth()
  const navigate = useNavigate()

  return (
    <>
      {isLoading && <Loading />}

      <section className={styles.section}>
        <div className={`__container text-center d-flex flex-column align-items-center justify-content-center`} data-width="medium">
          <figure>
            <img src={Logo} />
          </figure>

          <ul className='d-flex flex-column align-items-start justify-content-center'>
            <li>
              <h6>Stay</h6>
            </li>
            <li>
              <h3>Decentralized</h3>
            </li>
            <li>
              <h4>At your page</h4>
            </li>
          </ul>

          <figure>
            <img src={Hero} />
          </figure>
          <button className="btn mt-40" onClick={() => auth.connect().then(() => (window.location.href = '/usr/dashboard'))}>
            Connect
          </button>
        </div>
      </section>
    </>
  )
}

export default Home
