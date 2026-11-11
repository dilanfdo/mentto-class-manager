import { useState, type FormEvent } from 'react'
import { Badge, Button, Card, ErrorBox, Field, Input, Loading, Modal, PageHeader, Select } from '../../components/ui'
import { api, ApiError } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { DAYS, lkr } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClassGroup } from './types'

const empty = { name: '', subject: '', mode: 'online', day_of_week: '1', start_time: '18:30', monthly_fee: '2500', meeting_link: '', hall: '' }

export default function Classes() {
  const { has } = useAuth()
  const { data, error, loading, reload } = useApi<{ data: ClassGroup[] }>('/classes')
  const [editing, setEditing] = useState<{ id?: number; form: typeof empty } | null>(null)
  const [errors, setErrors] = useState<Record<string, string[]>>({})

  function open(c?: ClassGroup) {
    setErrors({})
    setEditing(c
      ? { id: c.id, form: { name: c.name, subject: c.subject ?? '', mode: c.mode, day_of_week: String(c.day_of_week ?? ''), start_time: c.start_time ?? '', monthly_fee: String(c.monthly_fee), meeting_link: c.meeting_link ?? '', hall: c.hall ?? '' } }
      : { form: { ...empty } })
  }

  async function save(e: FormEvent) {
    e.preventDefault()
    if (!editing) return
    const f = editing.form
    const body = { ...f, day_of_week: f.day_of_week ? Number(f.day_of_week) : null, start_time: f.start_time || null, monthly_fee: Number(f.monthly_fee), meeting_link: f.meeting_link || null, hall: f.hall || null }
    try {
      await api(editing.id ? `/classes/${editing.id}` : '/classes', { method: editing.id ? 'PATCH' : 'POST', body })
      setEditing(null)
      await reload()
    } catch (err) {
      setErrors(err instanceof ApiError ? { ...err.fields, _: [err.message] } : { _: [(err as Error).message] })
    }
  }

  const form = editing?.form
  const update = (patch: Partial<typeof empty>) => editing && setEditing({ ...editing, form: { ...editing.form, ...patch } })

  return (
    <>
      <PageHeader title="Classes" subtitle="Your batches and weekly schedule." actions={<Button onClick={() => open()}>Add class</Button>} />
      {error && <ErrorBox message={error} />}
      {loading && !data ? <Loading /> : (
        <div className="grid gap-3 md:grid-cols-2">
          {data?.data.map((c) => (
            <Card key={c.id} className="p-4">
              <div className="flex items-start justify-between gap-2">
                <div>
                  <button type="button" onClick={() => open(c)} className="text-left font-semibold hover:underline">{c.name}</button>
                  <p className="text-sm text-slate-500">{c.day_of_week ? DAYS[c.day_of_week] : 'No fixed day'}{c.start_time ? ` · ${c.start_time}` : ''}</p>
                </div>
                <Badge tone={c.mode === 'online' ? 'blue' : 'slate'}>{c.mode === 'online' ? 'Online' : 'In person'}</Badge>
              </div>
              <p className="mt-3 text-sm text-slate-600">{c.students_count} students · {lkr(c.monthly_fee)} a month{c.hall ? ` · ${c.hall}` : ''}</p>
              {c.meeting_link && <p className="mt-1 truncate text-xs text-slate-500">Link: {c.meeting_link}</p>}
            </Card>
          ))}
          {data?.data.length === 0 && <p className="text-sm text-slate-500">No classes yet. Add your first one.</p>}
        </div>
      )}

      {editing && form && (
        <Modal title={editing.id ? 'Edit class' : 'Add a class'} onClose={() => setEditing(null)}>
          <form onSubmit={save} className="grid gap-3">
            {errors._ && <ErrorBox message={errors._[0]} />}
            <Field label="Class name" error={errors.name?.[0]}><Input required value={form.name} onChange={(e) => update({ name: e.target.value })} /></Field>
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Subject" error={errors.subject?.[0]}><Input value={form.subject} onChange={(e) => update({ subject: e.target.value })} /></Field>
              <Field label="Mode" error={errors.mode?.[0]}>
                <Select value={form.mode} onChange={(e) => update({ mode: e.target.value })}><option value="online">Online</option><option value="physical">In person</option></Select>
              </Field>
              <Field label="Day" error={errors.day_of_week?.[0]}>
                <Select value={form.day_of_week} onChange={(e) => update({ day_of_week: e.target.value })}>
                  <option value="">No fixed day</option>
                  {DAYS.slice(1).map((d, i) => <option key={d} value={i + 1}>{d}</option>)}
                </Select>
              </Field>
              <Field label="Start time" error={errors.start_time?.[0]}><Input type="time" value={form.start_time} onChange={(e) => update({ start_time: e.target.value })} /></Field>
              <Field label="Monthly fee (LKR)" error={errors.monthly_fee?.[0]}><Input required type="number" min={0} value={form.monthly_fee} onChange={(e) => update({ monthly_fee: e.target.value })} /></Field>
              {has('halls') && <Field label="Hall" error={errors.hall?.[0]}><Input value={form.hall} onChange={(e) => update({ hall: e.target.value })} /></Field>}
            </div>
            {has('online_links') && form.mode === 'online' && (
              <Field label="Zoom or Meet link (shown only to students who paid)" error={errors.meeting_link?.[0]}><Input type="url" value={form.meeting_link} onChange={(e) => update({ meeting_link: e.target.value })} /></Field>
            )}
            <div className="flex gap-2 pt-2"><Button type="submit">Save</Button><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button></div>
          </form>
        </Modal>
      )}
    </>
  )
}
