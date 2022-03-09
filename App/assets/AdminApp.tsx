import React from 'react'

import ConfigProvider from './providers/Config/Provider'
import AppContainer from './components/AppContainer'

const AdminApp: React.FC = () => (
  <ConfigProvider>
    <AppContainer />
  </ConfigProvider>
)

export default AdminApp
