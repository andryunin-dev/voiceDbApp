'use strict';

import { decode } from "/../node_modules/js-base64"
// import axios from 'axios'
// import check from 'check-types'
//
// const URL_AUTH_LOGIN = 'netcmdb-loc.rs.ru:3010/auth/login'
// const URL_REFRESH_TOKEN = 'netcmdb-loc.rs.ru:3010/auth/refreshToken'
//
// const getToken = () => localStorage.getItem('token')
// const setToken = (token) => localStorage.setItem('token', token)
// const getTokenInfo = () => localStorage.getItem('user')
// const setTokenInfo = (tokenInfo) => localStorage.setItem('user', tokenInfo)
// const clearToken = () => {
//   localStorage.removeItem('user')
//   localStorage.removeItem('token')
// }
//
// const checkTokenTime = (token) => {
//   // return false if difference between current time and iat time less 1 minute
//   const iat = token && check.integer(token.iat) && token.iat
//   const timeDiff = (new Date()).getTime() - iat * 1000
//   return  timeDiff >= 0 && timeDiff < 60 * 1000
// }
//
// const getTokenTimeBeforeRefresh = (token) => {
//   // return false if difference between current time and iat time less 1 minute
//   const exp = token && check.integer(token.exp) && token.exp
//   return exp * 1000 - (new Date()).getTime()
// }
//
// const isLoggedIn = () => {
//   const token = getToken()
//   const tokenParts = check.string(token) && token.split('.').length === 3 && token.split('.')
//   const tokenInfo = tokenParts && check.string(tokenParts[1]) && JSON.parse(decode(tokenParts[1]))
//   return tokenInfo && getTokenTimeBeforeRefresh(tokenInfo) > 0
// }
//
// const fetchToken = async ({username, password}) => {
//   try {
//     const {data} = !! username
//       ? await axios.post(URL_AUTH_LOGIN, {email: username, password}, {withCredentials: true})
//       : await axios.get(URL_REFRESH_TOKEN, {withCredentials: true})
//     const token = data && data.accessToken
//     if (!check.string(token) || token.split('.').length !== 3) {
//       return {errorCode: 500, errorMessage: 'Auth service error, invalid token'}
//     } else {
//       const tokenParts = token.split('.')
//       const user = check.string(tokenParts[1]) && JSON.parse(decode(tokenParts[1]))
//       if (checkTokenTime(user)) {
//         return {token, user}
//       } else {
//         return {error: 401, errorMessage: 'Incorrect client time'}
//       }
//     }
//   } catch (e) {
//     if (e.response && e.response.status) {
//       const errorCode = e.response.status
//       const errorMessage = e.response.data && e.response.data.message
//       return {errorCode, errorMessage}
//     } else {
//       const errorCode = 500
//       const errorMessage = 'Unexpected auth service error'
//       return {errorCode, errorMessage}
//     }
//   }
// }

function Test() {
  console.log('test login status: ')
  return (<div>Test</div>)
}


let domContainer = document.querySelector('#react-auth');
ReactDOM.render(<Test />, domContainer);