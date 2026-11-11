import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Button, Card, ErrorBox, Field, Input, PageHeader, Select } from '../../components/ui'
import { api, ApiError } from '../../lib/api'

export default function NewClient() {
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', type: 'tutor', status: 'trial', contact_name: '', contact_email: '', contact_phone: '', owner_password: '', notes: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    setMessage(null)
    try {
      const res = await api<{ data: { id: number } }>('/admin/tenants', { method: 'POST', body: form })
      navigate(`/admin/clients/${res.data.id}`)
    } catch (err) {
      if (err instanceof ApiError) setErrors(err.fields)
      setMessage((err as Error).message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <Link to="/admin/clients" className="text-sm text-slate-500 hover:text-slate-900">← All clients</Link>
      <PageHeader title="Add a client" subtitle="Creates their account, an owner login and the usual features for their type. You can change features afterwards." />
      <Card className="max-w-2xl p-5">
        <form onSubmit={submit} className="grid gap-4">
          {message && <ErrorBox message={message} />}
          <Field label="Class or institute name" error={errors.name?.[0]}><Input required value={form.name} onChange={set('name')} /></Field>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Type" error={errors.type?.[0]}>
              <Select value={form.type} onChange={set('type')}><option value="tutor">Individual tutor</option><option value="institute">Institute</option></Select>
            </Field>
            <Field label="Status" error={errors.status?.[0]}>
              <Select value={form.status} onChange={set('status')}><option value="trial">Trial</option><option value="active">Active</option></Select>
            </Field>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Contact name" error={errors.contact_name?.[0]}><Input required value={form.contact_name} onChange={set('contact_name')} /></Field>
            <Field label="Phone" error={errors.contact_phone?.[0]}><Input type="tel" value={form.contact_phone} onChange={set('contact_phone')} /></Field>
          </div>
          <Field label="Owner login email" error={errors.contact_email?.[0]}><Input required type="email" value={form.contact_email} onChange={set('contact_email')} /></Field>
          <Field label="Temporary password (at least 8 characters)" error={errors.owner_password?.[0]}><Input required type="password" minLength={8} autoComplete="new-password" value={form.owner_password} onChange={set('owner_password')} /></Field>
          <Field label="Notes (only you see these)" error={errors.notes?.[0]}><Input value={form.notes} onChange={set('notes')} /></Field>
          <div><Button type="submit" disabled={saving}>{saving ? 'Creating…' : 'Create client'}</Button></div>
        </form>
      </Card>
    </>
  )
}
