import { useEffect, useState } from 'react'
import { Outlet, useLocation, Link, NavLink, useNavigate, useNavigation } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { useAuth } from './../contexts/AuthContext'
import MaterialIcon from './helper/MaterialIcon'
import { MenuIcon } from './components/icons'
import styles from './Layout.module.scss'
import Logo from './../../src/assets/logo.svg'

let link = [
  {
    name: 'Dashboard',
    icon: 'dashboard',
    path: 'dashboard',
  },
  {
    name: 'ALLO',
    icon: 'hub',
    path: 'allo',
  },
  {
    name: 'Profile',
    icon: 'app_registration',
    path: 'profile',
  },
  {
    name: 'Strategy',
    icon: 'strategy',
    path: 'strategy',
  },
  {
    name: 'Pool',
    icon: 'finance_chip',
    path: 'pool',
  },
  {
    name: 'IPFS',
    icon: 'dns',
    path: 'ipfs',
  },
]

export default function Root() {
  const [network, setNetwork] = useState()
  const [isLoading, setIsLoading] = useState()
  const noHeader = ['/sss']
  const auth = useAuth()
  const navigate = useNavigate()
  const navigation = useNavigation()
  const location = useLocation()

  const handleNavLink = (route) => {
    if (route) navigate(route)
    handleOpenNav()
  }

  const handleOpenNav = () => {
    document.querySelector('#modal').classList.toggle('open')
    document.querySelector('#modal').classList.toggle('blur')
    document.querySelector('.cover').classList.toggle('showCover')
  }
  useEffect(() => {}, [])

  return (
    <>
      <Toaster />

      <div className={styles.rootLayout}>
        {
          <header className={`${styles.header} d-flex align-items-center justify-content-between`}>
            <Link to={'/'}>
              <div className={`${styles.header__logo} d-flex align-items-center`}>
                <figure>
                  <img src={Logo} alt={`logo`} />
                </figure>
                Azeropage
              </div>
            </Link>

            <div className={`d-flex align-items-center`} style={{ columnGap: '1rem' }}>
              <ul className={`d-flex flex-column align-items-center`}>
                <li>{auth.wallet && `${auth.wallet.slice(0, 4)}...${auth.wallet.slice(44)}`}</li>
              </ul>

              <button className={styles.navButton} onClick={() => handleNavLink()}>
                <MenuIcon />
              </button>
            </div>
          </header>
        }

        <main>
          <Outlet />
        </main>
      </div>

      <div className="cover" onClick={() => handleOpenNav()} />
      <nav className={`${styles.nav} animate`} id="modal">
        <figure>
          <img src={Logo} alt={`logo`} />
        </figure>
        <ul>
        <li className="">
            <button onClick={() => handleNavLink(`/`)}>
              <MaterialIcon name="home" />
              <span>Home</span>
            </button>
          </li>
          <li className="">
            <button onClick={() => handleNavLink(`/usr/dashboard`)}>
              <MaterialIcon name="dashboard" />
              <span>Dashboard</span>
            </button>
          </li>
          <li className="">
            <button onClick={() => handleNavLink(`/about`)}>
              <MaterialIcon name="info" />
              <span>About us</span>
            </button>
          </li>
          <li className="">
            <button onClick={() => handleNavLink(`/feedback`)}>
              <MaterialIcon name="feedback" />
              <span>Feedback</span>
            </button>
          </li>
        </ul>

        <small>{`Version ${import.meta.env.VITE_VERSION}`}</small>
      </nav>
    </>
  )
}
