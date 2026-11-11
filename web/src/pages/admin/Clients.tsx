import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Badge, Card, ErrorBox, Input, Loading, PageHeader, Select, Table, statusTone } from '../../components/ui'
import { useAuth } from '../../lib/auth'
import { lkr } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClientSummary } from './types'

export default function Clients() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const query = new URLSearchParams({ ...(search && { search }), ...(status && { status }) }).toString()
  const { data, error, loading } = useApi<{ data: ClientSummary[] }>(`/admin/tenants${query ? `?${query}` : ''}`)
  const { profile } = useAuth()
  const navigate = useNavigate()
  const names = profile?.feature_names ?? {}

  return (
    <>
      <PageHeader title="Clients" subtitle="Tutors and institutes using Class Manager. Open one to switch features on or off." actions={<Link to="/admin/clients/new" className="rounded-lg bg-brand px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add client</Link>} />
      <div className="mb-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_180px]">
        <Input type="search" placeholder="Search by name or email" aria-label="Search clients" value={search} onChange={(e) => setSearch(e.target.value)} />
        <Select aria-label="Filter by status" value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="trial">Trial</option>
          <option value="suspended">Suspended</option>
        </Select>
      </div>
      {error && <ErrorBox message={error} />}
      {loading && !data ? (
        <Loading />
      ) : (
        <Card>
          <Table head={['Client', 'Status', 'Students', 'Add-ons', 'Monthly']}>
            {data?.data.map((c) => (
              <tr key={c.id} className="cursor-pointer hover:bg-slate-50" onClick={() => navigate(`/admin/clients/${c.id}`)}>
                <td className="px-4 py-3">
                  <Link to={`/admin/clients/${c.id}`} className="font-medium text-slate-900 hover:underline" onClick={(e) => e.stopPropagation()}>{c.name}</Link>
                  <p className="text-xs text-slate-500">{c.type === 'tutor' ? 'Tutor' : 'Institute'} · {c.contact_email}</p>
                </td>
                <td className="px-4 py-3"><Badge tone={statusTone(c.status)}>{c.status}</Badge></td>
                <td className="px-4 py-3 tabular-nums">{c.active_students}</td>
                <td className="px-4 py-3">
                  <div className="flex max-w-xs flex-wrap gap-1">
                    {c.addons.length ? c.addons.map((a) => <Badge key={a} tone="blue">{names[a] ?? a}</Badge>) : <span className="text-xs text-slate-400">None</span>}
                  </div>
                </td>
                <td className="whitespace-nowrap px-4 py-3 tabular-nums">{lkr(c.monthly_estimate)}</td>
              </tr>
            ))}
            {data?.data.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-500">No clients match.</td></tr>
            )}
          </Table>
        </Card>
      )}
    </>
  )
}
