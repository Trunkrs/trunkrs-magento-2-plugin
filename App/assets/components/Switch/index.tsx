import React from 'react'
import clsx from 'clsx'

import './Switch.scss'

interface SwitchProps {
  checked?: boolean
  tabIndex?: number
  onChange?: () => void | Promise<void>
}

const Switch: React.FC<SwitchProps> = ({
  checked,
  tabIndex = 1,
  children,
  onChange,
}) => (
  <span className="tr-mage-switchContainer">
    {/* eslint-disable-next-line jsx-a11y/click-events-have-key-events */}
    <div
      tabIndex={tabIndex}
      role="button"
      className={clsx('tr-mage-switch', { 'tr-mage-switch-checked': checked })}
      onClick={onChange}
    >
      <span className="tr-mage-switchKnob" />
    </div>

    <span className="tr-mage-switchContent">{children}</span>
  </span>
)

export default Switch
