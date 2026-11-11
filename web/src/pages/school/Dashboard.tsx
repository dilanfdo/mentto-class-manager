import { Link } from 'react-router-dom'
import { Badge, Card, ErrorBox, Loading, PageHeader, Stat } from '../../components/ui'
import { useAuth } from '../../lib/auth'
import { lkr } from '../../lib/format'
import { useApi } from '../../lib/useApi'

type DashboardData = {
  active_students: number
  classes: number
  collected_this_month: number
  pending_payments: number
  today: { id: number; name: string; start_time: string | null; mode: string; hall?: string | null; students_count: number }[]
}

export default function Dashboard() {
  const { profile, has } = useAuth()
  const { data, error, loading } = useApi<DashboardData>('/dashboard')
  if (loading && !data) return <Loading />
  if (error || !data) return <ErrorBox message={error ?? 'Could not load your dashboard.'} />

  return (
    <>
      <PageHeader title={`Hello, ${profile?.user.name.split(' ')[0]}`} subtitle="Your classes and fees today." />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Stat label="Active students" value={data.active_students} />
        <Stat label="Classes" value={data.classes} />
        {has('fees') && <Stat label="Collected this month" value={lkr(data.collected_this_month)} />}
        {has('fees') && <Stat label="Slips to check" value={data.pending_payments} hint={data.pending_payments ? 'Waiting for your approval' : 'All caught up'} />}
      </div>
      <Card className="mt-6 p-5">
        <h2 className="font-semibold">Today's classes</h2>
        {data.today.length === 0 ? (
          <p className="mt-2 text-sm text-slate-500">No classes today.</p>
        ) : (
          <ul className="mt-3 divide-y divide-slate-100">
            {data.today.map((c) => (
              <li key={c.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                <span><span className="mr-2 font-mono tabular-nums text-slate-500">{c.start_time ?? '--:--'}</span>{c.name}</span>
                <span className="flex items-center gap-2 text-slate-500">
                  {c.students_count} students
                  <Badge tone={c.mode === 'online' ? 'blue' : 'slate'}>{c.mode === 'online' ? 'Online' : c.hall ?? 'In person'}</Badge>
                </span>
              </li>
            ))}
          </ul>
        )}
      </Card>
      {has('fees') && data.pending_payments > 0 && (
        <p className="mt-4 text-sm"><Link to="/app/fees?status=pending" className="font-semibold text-brand hover:underline">Check {data.pending_payments} bank slips →</Link></p>
      )}
    </>
  )
}
