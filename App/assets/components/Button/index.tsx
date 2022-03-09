import React from 'react'
import clsx from 'clsx'

import './Button.scss'

interface ButtonProps {
  color: 'green' | 'blue' | 'white' | 'indigo'
  href?: string
  disabled?: boolean
  className?: string
  onClick?: () => void | Promise<void>
}

const Button: React.FC<ButtonProps> = ({
  color,
  href,
  disabled,
  className,
  children,
  onClick,
}) => {
  const classes = clsx(
    'tr-mage-button',
    {
      'tr-mage-blue': color === 'blue',
      'tr-mage-green': color === 'green',
      'tr-mage-white': color === 'white',
      'tr-mage-indigo': color === 'indigo',
    },
    className,
  )

  const element = !href ? (
    // eslint-disable-next-line jsx-a11y/control-has-associated-label
    <button type="button" onClick={onClick} />
  ) : (
    // eslint-disable-next-line jsx-a11y/anchor-has-content,jsx-a11y/control-has-associated-label
    <a href={href} target="_blank" rel="noreferrer noopener nofollow" />
  )

  return React.cloneElement(
    element,
    {
      className: classes,
      disabled,
    },
    children,
  )
}

export default Button
