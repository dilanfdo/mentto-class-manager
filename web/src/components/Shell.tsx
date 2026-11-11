import { useState, type ReactNode } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../lib/auth'

export type NavItem = { to: string; label: string; end?: boolean }

/** Sidebar on wide screens, a top menu on phones. */
export function Shell({ brand, nav, footer }: { brand: ReactNode; nav: NavItem[]; footer?: ReactNode }) {
  const { profile, logout } = useAuth()
  const [open, setOpen] = useState(false)

  const links = (
    <nav className="grid gap-0.5">
      {nav.map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          end={item.end}
          onClick={() => setOpen(false)}
          className={({ isActive }) => `rounded-lg px-3 py-2 text-sm font-medium ${isActive ? 'bg-brand-soft text-blue-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}`}
        >
          {item.label}
        </NavLink>
      ))}
    </nav>
  )

  const account = (
    <div className="border-t border-slate-200 pt-3 text-sm">
      <p className="truncate font-medium">{profile?.user.name}</p>
      <p className="truncate text-xs text-slate-500">{profile?.user.email}</p>
      <button type="button" onClick={() => void logout()} className="mt-2 text-xs font-semibold text-slate-600 underline underline-offset-2 hover:text-slate-900">Sign out</button>
    </div>
  )

  return (
    <div className="min-h-screen lg:grid lg:grid-cols-[240px_minmax(0,1fr)]">
      <aside className="hidden flex-col gap-6 border-r border-slate-200 bg-white p-4 lg:flex">
        <div>{brand}</div>
        <div className="flex-1">{links}</div>
        {footer}
        {account}
      </aside>

      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
        <div className="flex items-center justify-between">
          {brand}
          <button type="button" aria-expanded={open} onClick={() => setOpen(!open)} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium">Menu</button>
        </div>
        {open && <div className="mt-3 grid gap-3">{links}{footer}{account}</div>}
      </header>

      <main className="mx-auto w-full max-w-6xl p-4 sm:p-6 lg:p-8">
        <Outlet />
      </main>
    </div>
  )
}
