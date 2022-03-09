import React from 'react'

import ConnectButton, { LoginResult } from '../ConnectButton'

import './ConnectionPanel.scss'

interface ConnectionPanelProps {
  loading?: boolean
  onLoginDone?: (result: LoginResult) => void | Promise<void>
}

const ConnectionPanel: React.FC<ConnectionPanelProps> = ({
  loading,
  onLoginDone,
}) => (
  <div className="tr-mage-connectionPanel">
    <span className="tr-mage-panelHeader">
      <p>Platform connectie</p>
    </span>
    <span className="tr-mage-panelContent">
      <span className="tr-mage-connectionStatus">
        <h3>Niet verbonden met Trunkrs</h3>
        <p>
          Verbind uw winkel met het Trunkrs platform om te beginnen met
          verzenden.
        </p>
      </span>

      <span>
        <ConnectButton loading={loading} onLoginDone={onLoginDone} />
      </span>
    </span>
  </div>
)

export default ConnectionPanel
