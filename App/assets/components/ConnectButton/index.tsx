import React from 'react'
import { Auth0Error, Auth0Result, WebAuth } from 'auth0-js'
import clsx from 'clsx'

import Trunkrs from '../../shared/components/vectors/Trunkrs'
import Button from '../Button'

import './ConnectButton.scss'
import CircularProgress from '../CircularProgress'
import constants from '../../shared/constants'

const orgEventType = 'ORG_ID_RECEIVED'

export interface LoginResult {
  organizationId: string
  accessToken: string
}

interface ConnectButtonProps {
  className?: string
  loading?: boolean
  onLoginDone?: (result: LoginResult) => void | Promise<void>
}

const ConnectButton: React.FC<ConnectButtonProps> = ({
  className,
  loading,
  onLoginDone,
}) => {
  const orgIdRef = React.useRef<string>()

  const handleOrgIdReceived = React.useCallback((event: MessageEvent) => {
    const { type, organizationId } = event.data
    if (type === orgEventType) {
      orgIdRef.current = organizationId
    }
  }, [])

  const handleAuthorizeDone = React.useCallback(
    (error: null | Auth0Error, result: Auth0Result) => {
      window.removeEventListener('message', handleOrgIdReceived)

      if (error) {
        console.error(error)
        return
      }

      onLoginDone?.call(null, {
        organizationId: orgIdRef.current as string,
        accessToken: result.accessToken as string,
      })
    },
    [handleOrgIdReceived, onLoginDone],
  )

  const handleStartConnection = React.useCallback(() => {
    const state = btoa(JSON.stringify({ origin: window.location.origin }))
    const authClient = new WebAuth({
      domain: constants.auth0.domain,
      clientID: constants.auth0.clientId,
      audience: constants.auth0.audience,
      state,
      redirectUri: constants.auth0.redirectUrl,
    })

    authClient.popup.authorize(
      {
        domain: constants.auth0.domain,
        clientId: constants.auth0.clientId,
        audience: constants.auth0.audience,
        redirectUri: constants.auth0.redirectUrl,
        responseType: 'token',
      },
      handleAuthorizeDone,
    )

    window.removeEventListener('message', handleOrgIdReceived)
    window.addEventListener('message', handleOrgIdReceived)
  }, [handleAuthorizeDone, handleOrgIdReceived])

  return (
    <Button
      color="green"
      disabled={loading}
      className={clsx('tr-mage-connectButton', className)}
      onClick={handleStartConnection}
    >
      {!loading ? (
        <Trunkrs className="tr-mage-logo" variant="indigo" />
      ) : (
        <CircularProgress size={32} thickness={2} />
      )}

      <p className="tr-mage-buttonText">{loading ? 'Verbinden' : 'Verbind'}</p>
    </Button>
  )
}

export default ConnectButton
