import React from 'react'
import ReactDOM from 'react-dom'
import AdminApp from './AdminApp'

const appElement = document.getElementById('trunkrs-app')
if (appElement) {
  ReactDOM.render(<AdminApp />, appElement)
}
