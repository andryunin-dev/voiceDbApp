import React, {useRef, useState, useEffect} from 'react'
import {BrowserRouter as Router, Switch, Route, Redirect, useRouteMatch, useLocation} from "react-router-dom";
import {
  isLoggedIn, setToken, setTokenInfo, refreshToken, clearToken,
  getTokenInfo, getTokenTimeBeforeRefresh
} from "./helpers";
import {LOGIN_PAGE} from "./constants";

const PrivateRoute = ({children, ...rest}) => {
  const {path, url} = useRouteMatch()
  const location = useLocation()
  console.log({path, url, location})
  return (
    <Route {...rest} render={({match}) => {
      return isLoggedIn() ? children : window.location.assign(LOGIN_PAGE)
    }} />
  )
}

const App = () => {
  const [tokenExpDate, setTokenExpDate] = useState(0)
  const timerId = useRef(null)
  console.log('isLogged', isLoggedIn())

  useEffect(() => {
    async function refresh() {
      const {user, token, errorCode, errorMessage} = await refreshToken()
      if (!errorCode && !errorMessage) {
        setToken(token)
        setTokenInfo(JSON.stringify(user))
        setTokenExpDate(user.exp)
      } else {
        clearToken()
      }
      return {user, token}
    }
    console.log('Date check', new Date((tokenExpDate - 2*60) * 1000))
    if (tokenExpDate === 0) {
      const tokenInfo = JSON.parse(getTokenInfo())
      const tokenExp = tokenInfo && tokenInfo.exp ? tokenInfo.exp : null
      setTokenExpDate(tokenExp)
    } else if (tokenExpDate && new Date((tokenExpDate - 2*60) * 1000) > new Date()) {
      const timeout = new Date((tokenExpDate - 2*60) * 1000) - new Date()
      console.log('setTimeout')
      timerId.current = setTimeout(() => refresh(), timeout)
      return () => {
        console.log('clear timeout')
        clearTimeout(timerId.current)
      }
    } else {
      console.log('before refresh')
      refresh().then(() => console.log('refresh')).catch(e => console.log(e.message))
    }
  }, [tokenExpDate])

  const clearTimerSchedule = () => {
    if (timerId.current) clearTimeout(timerId.current)
  }
  return (
    <Router>
      <PrivateRoute>
        <div/>
      </PrivateRoute>
    </Router>
  )
}
export default App