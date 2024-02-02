import React, { useContext, useEffect, useState } from 'react'
import { web3Enable, web3Accounts } from '@polkadot/extension-dapp'
import { useNavigate, useLocation, useNavigation } from 'react-router-dom'
// import { user } from '../util/api'
import toast, { Toaster } from 'react-hot-toast'

export const AuthContext = React.createContext()

export function useAuth() {
  return useContext(AuthContext)
}

/**
 * Connect wallet
 */
export const isWalletConnected = async () => {
  console.info('Check if wallet is connected...')

  try {
    let accounts = await web3.eth.getAccounts()
    console.log(accounts)
    if (accounts.length > 0) return accounts[0]
    else return false
  } catch (error) {
    toast.error(error.message)
  }
}

export function AuthProvider({ children }) {
  const [extensions, setExtensions] = useState()
  const [accounts, setAccounts] = useState()
  const [wallet, setWallet] = useState()
  const navigate = useNavigation()

  const logout = () => {
    localStorage.removeItem('accessToken')
    navigate('/login')
    setUser(null)
  }

  const connect = async () => {
    // extension-dapp API: connect to extensions; returns list of injected extensions
    const injectedExtensions = await web3Enable('Azeropage')
    setExtensions(injectedExtensions)

    // extension-dapp API: get accounts from extensions filtered by name
    const accounts = await web3Accounts({ extensions: ['aleph-zero-signer'] })
    console.log(accounts)

    if (accounts.length > 0) {
      setAccounts(accounts)
      setWallet(accounts[0].address)
    } else {
      navigate('/')
    }
  }

  useEffect(() => {
    connect()
    // console.log(location.pathname )
    // isWalletConnected().then((res) => {
    //   console.log(res)
    //   setIsConnected(res)
    //   setLoading(false)
    //   if (res) {
    //     console.log(res)
    //     setWallet(res)
    //     if (location.pathname ==='/')  navigate('/usr/dashboard')
    //   } else {
    //     navigate('/home')
    //   }
    // })
  }, [])

  const value = {
    extensions,
    accounts,
    wallet,
    connect,
  }

  if (!wallet && location.pathname !== '/') return <>Loading... !user</> //&& location.pathname !== '/home'

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
