const constants = {
  portalBaseUrl: process.env.TRUNKRS_PORTAL_BASE_URL,
  apiBaseUrl: process.env.TRUNKRS_API_BASE_URL,
  auth0: {
    domain: process.env.AUTH0_DOMAIN as string,
    clientId: process.env.AUTH0_CLIENT_ID as string,
    audience: process.env.AUTH0_AUDIENCE as string,
    redirectUrl: process.env.AUTH0_REDIRECT_URL as string,
  },
}

export default constants
