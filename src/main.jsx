import React, { Suspense, lazy } from 'react'
import ReactDOM from 'react-dom/client'
import { createBrowserRouter, RouterProvider, Navigate } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import './index.scss'
import './styles/global.scss'

import ErrorPage from './error-page'
import Loading from './routes/components/LoadingSpinner'
const Layout = lazy(() => import('./routes/layout.jsx'))
import SplashScreen, { loader as splashScreenLoader } from './routes/splashScreen.jsx'
import Dashboard from './routes/dashboard.jsx'
import Page from './routes/page.jsx'


const router = createBrowserRouter([
  {
    path: '/',
    element: (
      <Suspense fallback={<Loading />}>
        <AuthProvider>
          <Layout />
        </AuthProvider>
      </Suspense>
    ),
    errorElement: <ErrorPage />,
    children: [
      {
        path: 'usr',
        errorElement: <ErrorPage />,
        children: [
          {
            index: true,
            element: <Navigate to="/" replace />,
          },
          {
            path: 'dashboard',
            element: <Dashboard title={`Dashboard`} />,
          },
        ],
      },
      {
        path: ':username',
        element: <Page title={`Page`} />,
      },
    ],
  },
  {
    path: '/splashscreen',
    loader: splashScreenLoader,
    element: <SplashScreen title={`Welcome`} />,
  },
])

ReactDOM.createRoot(document.getElementById('root')).render(<RouterProvider router={router} />)
