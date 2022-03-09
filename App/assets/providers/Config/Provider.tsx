import React from 'react'
import { AxiosError } from 'axios'

import ConfigContext, { Configuration } from './index'
import { doConfigureRequest, doShippingReqisterRequest } from './helpers'

const initialConfigText = document.getElementById('__tr-mage-settings__')
  ?.innerText as string
const initialConfig = initialConfigText ? JSON.parse(initialConfigText) : {}

const ConfigProvider: React.FC = ({ children }) => {
  const [isWorking, setWorking] = React.useState(false)
  const [config, setConfig] = React.useState<Configuration>(initialConfig)

  const prepareConfig = React.useCallback(
    async (accessToken: string, orgId: string): Promise<void> => {
      try {
        setWorking(true)

        const pluginDetes = await doShippingReqisterRequest(
          accessToken,
          orgId,
          config.domainName,
          config.metaBag,
        )

        await doConfigureRequest(
          pluginDetes.accessToken,
          orgId,
          pluginDetes.organizationName,
          pluginDetes.integrationId,
          config.magentoToken,
          config.baseUrl,
        )

        setConfig({
          ...config,
          isConfigured: true,
          details: {
            integrationId: pluginDetes.integrationId,
            organizationId: orgId,
            organizationName: pluginDetes.organizationName,
          },
        })
      } catch (error) {
        const axiosError = error as AxiosError
        console.error(axiosError)
      } finally {
        setWorking(false)
      }
    },
    [config],
  )

  const contextValue = React.useMemo(
    () => ({
      isWorking,
      config,
      prepareConfig,
    }),
    [config, isWorking, prepareConfig],
  )

  return (
    <ConfigContext.Provider value={contextValue}>
      {children}
    </ConfigContext.Provider>
  )
}

export default ConfigProvider
