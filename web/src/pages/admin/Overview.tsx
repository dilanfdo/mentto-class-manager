import { Link } from 'react-router-dom'
import { Card, ErrorBox, Loading, PageHeader, Stat } from '../../components/ui'
import { lkr } from '../../lib/format'
import { useApi } from '../../lib/useApi'

type OverviewData = {
  clients: { total: number; by_status: Record<string, number>; by_type: Record<string, number> }
  active_students: number
  monthly_estimate: number
  feature_adoption: { key: string; name: string; group: 'core' | 'addon'; clients: number }[]
}

export default function Overview() {
  const { data, error, loading } = useApi<OverviewData>('/admin/overview')
  if (loading && !data) return <Loading />
  if (error || !data) return <ErrorBox message={error ?? 'Could not load the overview.'} />

  const { clients } = data
  const addons = data.feature_adoption.filter((f) => f.group === 'addon')

  return (
    <>
      <PageHeader title="Overview" subtitle="Everyone using Class Manager, at a glance." actions={<Link to="/admin/clients/new" className="rounded-lg bg-brand px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add client</Link>} />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Stat label="Clients" value={clients.total} hint={`${clients.by_type.tutor ?? 0} tutors · ${clients.by_type.institute ?? 0} institutes`} />
        <Stat label="Active" value={clients.by_status.active ?? 0} hint={`${clients.by_status.trial ?? 0} on trial · ${clients.by_status.suspended ?? 0} suspended`} />
        <Stat label="Active students" value={data.active_students.toLocaleString('en-LK')} hint="Across clients that aren't suspended" />
        <Stat label="Monthly revenue" value={lkr(data.monthly_estimate)} hint="Estimate for active clients, from quote prices" />
      </div>

      <Card className="mt-6 p-5">
        <h2 className="font-semibold">Add-on adoption</h2>
        <p className="text-sm text-slate-500">How many clients have each paid feature switched on.</p>
        <ul className="mt-4 grid gap-3">
          {addons.map((f) => (
            <li key={f.key} className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 text-sm sm:grid-cols-[220px_minmax(0,1fr)_40px]">
              <span className="truncate">{f.name}</span>
              <span className="order-3 col-span-2 h-2 rounded-full bg-slate-100 sm:order-none sm:col-span-1">
                <span className="block h-2 rounded-full bg-brand" style={{ width: `${clients.total ? (f.clients / clients.total) * 100 : 0}%` }} />
              </span>
              <span className="text-right tabular-nums text-slate-600">{f.clients}</span>
            </li>
          ))}
        </ul>
      </Card>
    </>
  )
}
