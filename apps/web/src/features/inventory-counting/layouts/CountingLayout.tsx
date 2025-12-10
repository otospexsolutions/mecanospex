import { Outlet } from 'react-router-dom'

export function CountingLayout() {
  return (
    <div className="p-6">
      <Outlet />
    </div>
  )
}
