import React from 'react'

interface LinkoutProps {
  className?: string
}

const Linkout: React.FC<LinkoutProps> = ({ className }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    className={className}
    viewBox="0 0 24 24"
    fill="none"
  >
    <path
      fillRule="evenodd"
      clipRule="evenodd"
      d="M2 2H8V4H4V20H20V16H22V22H2V2ZM18.4372 4.16L12.5199 4.16V2.16L21.8399 2.16L21.8399 11.48H19.8399L19.8399 5.5857L9.20706 16.2186L7.79285 14.8044L18.4372 4.16Z"
      fill="currentColor"
    />
  </svg>
)

export default Linkout
