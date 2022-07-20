import React from 'react'

import constants from '../../shared/constants'

import Check from '../vectors/Check'
import Button from '../Button'

import Linkout from '../vectors/Linkout'
import Switch from '../Switch'
import './DetailsPanel.scss'

interface DetailsPanelProps {
  isDisableAutoShipment: boolean
  integrationId: string
  organizationId: string
  organizationName: string
  onDisableShipment: () => void | Promise<void>
}

const DetailsPanel: React.FC<DetailsPanelProps> = ({
  isDisableAutoShipment,
  integrationId,
  organizationId,
  organizationName,
  onDisableShipment,
}) => {
  const manageUrl = `${constants.portalBaseUrl}/${organizationId}/settings/integrations/${integrationId}`

  return (
    <div className="tr-mage-detailsPanel">
      <div className="tr-mage-panelHeader">
        <p>Platform connectie</p>
      </div>
      <div className="tr-mage-panelContent">
        <div className="tr-mage-detailsPanel-connected">
          <Check className="tr-mage-checkVector" />
          <div>
            <h3>Deze winkel is verbonden</h3>
            <p>U bent klaar om uw bestellingen met Trunkrs te verzenden.</p>
          </div>
        </div>
        <div>
          <h4>Integratie nummer:</h4>
          <p>{integrationId}</p>
          <h4>Organisatie nummer:</h4>
          <p>{organizationId}</p>
          <h4>Organisatie naam:</h4>
          <p>{organizationName}</p>
        </div>
      </div>
      <div className="tr-mage-panelContent">
        <h4>Geavanceerde opties</h4>
        <br />
        <Switch checked={isDisableAutoShipment} onChange={onDisableShipment}>
          <p>Automatisch aanmaken van zendingen uitschakelen</p>
        </Switch>
      </div>
      <div className="tr-mage-panelFooter">
        <div>
          <Button href={manageUrl} color="indigo">
            <Linkout className="tr-mage-buttonVector" />
            Beheer
          </Button>
        </div>
      </div>
    </div>
  )
}

export default DetailsPanel
