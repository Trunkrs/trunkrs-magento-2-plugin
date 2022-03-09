import Axios from 'axios'

import constants from '../../shared/constants'

interface IntegrationResponse {
  integrationId: string
  organizationName: string
  accessToken: string
}

export const doShippingReqisterRequest = async (
  userAccessToken: string,
  orgId: string,
  domainName: string,
  meta: { [key: string]: string },
): Promise<IntegrationResponse> => {
  const { data } = await Axios.request<IntegrationResponse>({
    method: 'POST',
    baseURL: constants.apiBaseUrl,
    url: 'integrations',
    headers: {
      Authorization: `Bearer ${userAccessToken}`,
      'X-Organization-Id': orgId,
    },
    data: {
      type: 'Magento2',
      name: window.location.hostname,
      version: 1,
      meta,
      magento: {
        shopDomain: domainName,
        accessToken: userAccessToken,
      },
    },
  })

  return data
}

export const doConfigureRequest = async (
  accessToken: string,
  orgId: string,
  orgName: string,
  integrationId: string,
  magentoToken: string,
  baseUrl: string,
): Promise<void> => {
  await Axios.request<void>({
    method: 'POST',
    baseURL: baseUrl,
    url: 'rest/V1/trunkrs/integration-details',
    headers: {
      magentoToken,
    },
    data: {
      isConfigured: true,
      accessToken,
      details: {
        integrationId,
        organizationId: orgId,
        organizationName: orgName,
      },
    },
  })
}
