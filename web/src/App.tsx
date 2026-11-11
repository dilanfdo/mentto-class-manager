import type { ReactNode } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { Shell, type NavItem } from './components/Shell'
import { Loading } from './components/ui'
import { AuthProvider, useAuth } from './lib/auth'
import Login from './pages/Login'
import ClientDetail from './pages/admin/ClientDetail'
import Clients from './pages/admin/Clients'
import NewClient from './pages/admin/NewClient'
import Overview from './pages/admin/Overview'
import Attendance from './pages/school/Attendance'
import Classes from './pages/school/Classes'
import Dashboard from './pages/school/Dashboard'
import Exams from './pages/school/Exams'
import Fees from './pages/school/Fees'
import Reports from './pages/school/Reports'
import Students from './pages/school/Students'

const Brand = ({ sub }: { sub: string }) => (
  <div>
    <p className="font-semibold">Class Manager</p>
    <p className="text-xs text-slate-500">{sub}</p>
  </div>
)

function RequireRole({ admin, children }: { admin: boolean; children: ReactNode }) {
  const { profile, ready } = useAuth()
  if (!ready) return <Loading />
  if (!profile) return <Navigate to="/login" replace />
  const isAdmin = profile.tenant === null
  if (isAdmin !== admin) return <Navigate to={isAdmin ? '/admin' : '/app'} replace />
  return children
}

/** Pages for a feature the admin switched off are not routed at all. */
function SchoolRoutes() {
  const { profile, has } = useAuth()
  const nav: NavItem[] = [
    { to: '/app', label: 'Dashboard', end: true },
    { to: '/app/students', label: 'Students' },
    { to: '/app/classes', label: 'Classes' },
    ...(has('fees') ? [{ to: '/app/fees', label: 'Fees' }] : []),
    ...(has('attendance') ? [{ to: '/app/attendance', label: 'Attendance' }] : []),
    ...(has('exams') ? [{ to: '/app/exams', label: 'Exams' }] : []),
    ...(has('reports') ? [{ to: '/app/reports', label: 'Reports' }] : []),
  ]

  return (
    <Routes>
      <Route element={<Shell brand={<Brand sub={profile?.tenant?.name ?? ''} />} nav={nav} />}>
        <Route index element={<Dashboard />} />
        <Route path="students" element={<Students />} />
        <Route path="classes" element={<Classes />} />
        {has('fees') && <Route path="fees" element={<Fees />} />}
        {has('attendance') && <Route path="attendance" element={<Attendance />} />}
        {has('exams') && <Route path="exams" element={<Exams />} />}
        {has('reports') && <Route path="reports" element={<Reports />} />}
        <Route path="*" element={<Navigate to="/app" replace />} />
      </Route>
    </Routes>
  )
}

function AdminRoutes() {
  const nav: NavItem[] = [
    { to: '/admin', label: 'Overview', end: true },
    { to: '/admin/clients', label: 'Clients' },
  ]
  return (
    <Routes>
      <Route element={<Shell brand={<Brand sub="NexusDigitalLabs admin" />} nav={nav} />}>
        <Route index element={<Overview />} />
        <Route path="clients" element={<Clients />} />
        <Route path="clients/new" element={<NewClient />} />
        <Route path="clients/:id" element={<ClientDetail />} />
        <Route path="*" element={<Navigate to="/admin" replace />} />
      </Route>
    </Routes>
  )
}

function Home() {
  const { profile, ready } = useAuth()
  if (!ready) return <Loading />
  return <Navigate to={!profile ? '/login' : profile.tenant ? '/app' : '/admin'} replace />
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/admin/*" element={<RequireRole admin><AdminRoutes /></RequireRole>} />
          <Route path="/app/*" element={<RequireRole admin={false}><SchoolRoutes /></RequireRole>} />
          <Route path="*" element={<Home />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}
