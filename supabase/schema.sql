-- Smart Cards schema. Run once in Supabase → SQL Editor → New query → Run.

create table if not exists public.companies (
  id          uuid primary key default gen_random_uuid(),
  name        text not null,
  tagline     text,
  logo_url    text,
  phone       text,
  email       text,
  website     text,
  facebook    text,
  address     text,
  brand_color text not null default '#0b5cad',
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);

create table if not exists public.staff (
  id          uuid primary key default gen_random_uuid(),
  company_id  uuid not null references public.companies(id) on delete cascade,
  slug        text not null unique check (slug ~ '^[a-z0-9-]{2,80}$'),
  prefix      text,
  full_name   text not null,
  role        text,
  department  text,
  photo_url   text,
  phones      text,           -- one per line, e.g. "Mobile: +95 9 ..."
  email       text,
  viber       text,
  address     text,           -- empty = use company address
  notes       text,
  is_active   boolean not null default true,
  sort_order  int not null default 0,
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);

create index if not exists staff_company_idx on public.staff(company_id);

-- Only the PHP server (secret key) reads/writes these tables. RLS on + no policies
-- means the public anon key cannot touch them.
alter table public.companies enable row level security;
alter table public.staff     enable row level security;
grant all on public.companies, public.staff to service_role;

-- Public bucket for logos and staff photos (2 MB limit, images only).
insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('media', 'media', true, 2097152, array['image/png', 'image/jpeg', 'image/webp'])
on conflict (id) do nothing;
