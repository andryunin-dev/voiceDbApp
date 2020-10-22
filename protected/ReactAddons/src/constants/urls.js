const DEVELOPED_BASE_URL = 'netcmdb-loc.rs.ru:8082'
// const DEVELOPED_BASE_URL = 'netcmdb.rs.ru'
export const BASE_URL = (() => {
  const protocol = window.location.protocol
  const hostname = window.location.hostname
  const port = window.location.port
  const developMode = true || hostname === 'localhost' || hostname === 'test.rs.ru'
  return developMode ? `${protocol}//${DEVELOPED_BASE_URL}` : `${protocol}//${hostname}${port==='' ? '' : ':'}${port}`
})()
console.log("BASE API URL", BASE_URL)


// Top menu URLs
//internal links
export const DICT_REG_CENTERS_MAPPING= `/vra/rc/mapping`
export const UNREGISTERED_PHONES = `/vra/tools/unregisteredPhones`
export const CUCM_ROUTES = '/vra/tools/cucmRouting'
export const TEST_TOOLS = `/vra/tools/testing`
export const LOGIN_PAGE = `${BASE_URL}/vra/login`
export const URL_AUTH_LOGIN = `${BASE_URL}/auth/login`
export const URL_AUTH_LOGOUT = `${BASE_URL}/auth/logout`
export const URL_REFRESH_TOKEN = `${BASE_URL}/auth/refreshToken`