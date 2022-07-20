import React from 'react'

import useConfig from '../../providers/Config/useConfig'

import { LoginResult } from '../ConnectButton'
import TrunkrsFull from '../../shared/components/vectors/TrunkrsFull'
import CenteredContainer from '../CenteredContainer'

import ConnectionPanel from '../ConnectionPanel'
import DetailsPanel from '../DetailsPanel'

import './AppContainer.scss'

const AppContainer: React.FC = () => {
  const {
    isWorking,
    disableAutoShipmentCreation,
    config,
    prepareConfig,
    onDisableAutoShipment,
  } = useConfig()

  const handleLoginDone = React.useCallback(
    async (result: LoginResult): Promise<void> =>
      prepareConfig(result.accessToken, result.organizationId),
    [prepareConfig],
  )

  const handleDisableAutoShipment = React.useCallback(async () => {
    await onDisableAutoShipment()
  }, [onDisableAutoShipment])

  return (
    <CenteredContainer>
      <TrunkrsFull className="tr-mage-trunkrsFull" />

      {!config?.isConfigured ? (
        <ConnectionPanel loading={isWorking} onLoginDone={handleLoginDone} />
      ) : (
        <>
          <DetailsPanel
            isDisableAutoShipment={disableAutoShipmentCreation}
            integrationId={config.details.integrationId}
            organizationId={config.details.organizationId}
            organizationName={config.details.organizationName}
            onDisableShipment={handleDisableAutoShipment}
          />
        </>
      )}
    </CenteredContainer>
  )
}

export default AppContainer
