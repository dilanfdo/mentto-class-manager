import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Badge, Button, Card, ErrorBox, Loading, PageHeader, Select, Stat, Toggle, statusTone } from '../../components/ui'
import { api } from '../../lib/api'
import { lkr } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClientDetail as Detail, FeatureRow } from './types'

export default function ClientDetail() {
  const { id } = useParams()
  const { data, error, loading, reload } = useApi<{ data: Detail }>(`/admin/tenants/${id}`)
  const [saving, setSaving] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)

  if (loading && !data) return <Loading />
  if (error || !data) return <ErrorBox message={error ?? 'Client not found.'} />
  const client = data.data

  async function toggle(feature: FeatureRow, enabled: boolean) {
    setSaving(feature.key)
    setActionError(null)
    try {
      await api(`/admin/tenants/${client.id}/features/${feature.key}`, { method: 'PUT', body: { enabled } })
      await reload()
    } catch (e) {
      setActionError((e as Error).message)
    } finally {
      setSaving(null)
    }
  }

  async function setStatus(status: string) {
    if (status === 'suspended' && !confirm(`Suspend ${client.name}? Their staff and students will be locked out until you reactivate them.`)) return
    setActionError(null)
    try {
      await api(`/admin/tenants/${client.id}`, { method: 'PATCH', body: { status } })
      await reload()
    } catch (e) {
      setActionError((e as Error).message)
    }
  }

  const groups: { title: string; hint: string; rows: FeatureRow[] }[] = [
    { title: 'Add-ons', hint: 'Paid extras. Switching one on adds it to the monthly estimate.', rows: client.feature_list.filter((f) => f.group === 'addon') },
    { title: 'Included in every plan', hint: 'On by default. Locked ones are needed by everything else.', rows: client.feature_list.filter((f) => f.group === 'core') },
  ]

  return (
    <>
      <Link to="/admin/clients" className="text-sm text-slate-500 hover:text-slate-900">← All clients</Link>
      <PageHeader
        title={client.name}
        subtitle={<>{client.type === 'tutor' ? 'Individual tutor' : 'Institute'} · {client.contact_name} · {client.contact_email}{client.contact_phone ? ` · ${client.contact_phone}` : ''}</>}
        actions={
          <div className="flex items-center gap-2">
            <Badge tone={statusTone(client.status)}>{client.status}</Badge>
            <Select aria-label="Account status" value={client.status} onChange={(e) => void setStatus(e.target.value)}>
              <option value="active">Active</option>
              <option value="trial">Trial</option>
              <option value="suspended">Suspended</option>
            </Select>
          </div>
        }
      />
      {actionError && <div className="mb-4"><ErrorBox message={actionError} /></div>}

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Stat label="Active students" value={client.active_students} />
        <Stat label="Classes" value={client.counts.classes} />
        <Stat label="Collected this month" value={lkr(client.counts.collected_this_month)} hint="By the client from their students" />
        <Stat label="Monthly to us" value={lkr(client.monthly_estimate)} hint="Estimate from quote prices" />
      </div>

      {groups.map((group) => (
        <Card key={group.title} className="mt-6">
          <div className="border-b border-slate-200 px-5 py-4">
            <h2 className="font-semibold">{group.title}</h2>
            <p className="text-sm text-slate-500">{group.hint}</p>
          </div>
          <ul className="divide-y divide-slate-100">
            {group.rows.map((f) => (
              <li key={f.key} className="flex items-start justify-between gap-4 px-5 py-3.5">
                <div className="min-w-0">
                  <p className="font-medium">
                    {f.name}
                    {f.locked && <span className="ml-2"><Badge>Always on</Badge></span>}
                  </p>
                  <p className="text-sm text-slate-500">{f.description}</p>
                  <p className="mt-0.5 text-xs text-slate-400">
                    {f.group === 'addon' && (f.per_student ? `LKR ${f.per_student} a student, min ${lkr(f.monthly)} a month` : f.monthly ? `${lkr(f.monthly)} a month` : 'One-time set-up only')}
                    {f.updated_by && ` · changed by ${f.updated_by}${f.updated_at ? ` on ${new Date(f.updated_at).toLocaleDateString('en-GB')}` : ''}`}
                  </p>
                </div>
                <Toggle label={`${f.name} for ${client.name}`} checked={f.enabled} disabled={f.locked || saving === f.key} onChange={(v) => void toggle(f, v)} />
              </li>
            ))}
          </ul>
        </Card>
      ))}

      <Card className="mt-6 p-5">
        <h2 className="font-semibold">Logins</h2>
        <ul className="mt-3 divide-y divide-slate-100 text-sm">
          {client.users.map((u) => (
            <li key={u.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
              <span>{u.name} <span className="text-slate-500">· {u.email}</span></span>
              <Badge>{u.role}</Badge>
            </li>
          ))}
        </ul>
        {client.notes && <p className="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">{client.notes}</p>}
      </Card>

      {client.status === 'suspended' && (
        <div className="mt-4"><Button onClick={() => void setStatus('active')}>Reactivate client</Button></div>
      )}
    </>
  )
}
