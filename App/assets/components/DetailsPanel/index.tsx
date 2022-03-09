import React from 'react'

import constants from '../../shared/constants'

import Check from '../vectors/Check'
import Button from '../Button'

import './DetailsPanel.scss'
import Linkout from '../vectors/Linkout'

interface DetailsPanelProps {
  integrationId: string
  organizationId: string
  organizationName: string
}

const DetailsPanel: React.FC<DetailsPanelProps> = ({
  integrationId,
  organizationId,
  organizationName,
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
