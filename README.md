# Smart Cards — Custom QR digital business cards (PHP + Supabase + Vercel)

ဝန်ထမ်းတစ်ယောက်ချင်းစီအတွက် QR code ပြုလုပ်ပေးသည်။ Scan ဖတ်လိုက်ရင် card page တစ်ခု ပွင့်လာမယ်။
**Save to contacts** ကိုနှိပ်ရင် ဖုန်းထဲက Contacts ထဲကို **ဓာတ်ပုံ၊ company၊ ရာထူး၊ ဖုန်းနံပါတ်၊ email၊ လိပ်စာ၊ website**
အကုန် အလိုအလျောက် ဝင်သွားမယ်။ Android ရော iPhone မှာပါ အလုပ်လုပ်ပါတယ်။

## Features
- Company အများကြီး ထည့်လို့ရတယ်: logo၊ tagline၊ ဖုန်း၊ email၊ website၊ Facebook၊ လိပ်စာ၊ brand color
- Company တစ်ခုစီမှာ ဝန်ထမ်း အများကြီး ထည့်လို့ရတယ်: ဓာတ်ပုံ၊ ရာထူး၊ ဌာန၊ ဖုန်းနံပါတ်များ၊ email၊ Viber၊ မှတ်ချက်
- QR designer: dot style၊ အရောင်၊ အလယ်မှာ logo၊ PNG/SVG download (print အတွက် 2400px အထိ)
- QR ထဲမှာ link တိုလေးပဲ ထည့်ထားလို့ QR ပုံစံက ရိုးရှင်းပြီး မြန်မြန် scan ဖတ်လို့ရတယ်။
  အချက်အလက်ပြင်ချင်ရင် admin မှာ ပြင်လိုက်ရုံပါပဲ။ QR အသစ် ပြန်ထုတ်စရာ မလိုပါဘူး။

## 1. Supabase setup
1. https://supabase.com/dashboard → **New project** (ဥပမာ name: `smart-cards`, region: Singapore)
2. **SQL Editor → New query** → [`supabase/schema.sql`](supabase/schema.sql) ထဲက code ကို paste ပြီး **Run** နှိပ်ပါ
3. **Project Settings → API Keys** မှာ ဒါတွေကို ကူးယူပါ:
   - **Project URL** → `SUPABASE_URL`
   - **Secret key** (`sb_secret_...`) ဒါမှမဟုတ် legacy `service_role` key → `SUPABASE_SECRET_KEY`
   ⚠️ ဒီ key ကို ဘယ်သူ့ကိုမှ မပေးပါနဲ့၊ GitHub ပေါ်လည်း မတင်ပါနဲ့

## 2. Vercel deploy
**နည်း A: CLI ကနေ**
```bash
cd qr-vcard
npx vercel login
npx vercel            # project အသစ် create လုပ်မယ်
npx vercel env add SUPABASE_URL
npx vercel env add SUPABASE_SECRET_KEY
npx vercel env add ADMIN_PASSWORD
npx vercel env add APP_SECRET        # `openssl rand -hex 32` နဲ့ ထုတ်ထားတဲ့ random string
npx vercel --prod
```

**နည်း B: GitHub ကနေ**
GitHub repo ထဲ push လုပ်ပြီး → vercel.com → **Add New → Project → Import** → Environment Variables ၄ ခု ထည့် → **Deploy**

Env var ပြောင်းပြီးတိုင်း **Redeploy** လုပ်ဖို့ လိုပါတယ်။

## 3. အသုံးပြုပုံ
1. `https://YOUR-APP.vercel.app/admin` → password ရိုက်ထည့်ပါ
2. **Add company** → logo တင်ပြီး info တွေ ဖြည့်ပါ
3. **Add staff** → ဓာတ်ပုံ၊ နာမည်၊ ရာထူး၊ ဖုန်းနံပါတ်တွေ ဖြည့်ပါ (တစ်ကြောင်းကို နံပါတ်တစ်ခု၊ `Work: +95 1 ...` လို label ထည့်လို့ရ)
4. **QR** → ပုံစံ ရွေးပြီး PNG/SVG download → ကတ်ပြားပေါ် print ထုတ်ပါ
5. **Print မထုတ်ခင် ဖုန်း camera နဲ့ အရင် scan စမ်းကြည့်ပါ**

### QR scan လုပ်ရင် ဘာဖွင့်မလဲ (QR page မှာ ရွေးလို့ရ)
- **Card page** (အကြံပြုပါတယ်): ဓာတ်ပုံနဲ့ info ပြပြီး Call / Email / Viber / Map ခလုတ်တွေ ပါတယ်
- **.vcf direct**: contact save screen ကို တန်းဖွင့်ပေးတယ်

## Local မှာ run ကြည့်ချင်ရင်
```bash
cp .env.example .env      # တန်ဖိုးတွေ ဖြည့်ပါ
php -S localhost:8000 api/index.php
# http://localhost:8000/admin
```

## Structure
```
api/index.php        Vercel function (route အားလုံး ဒီကို ဝင်တယ်)
lib/routes.php       URL routing
lib/handlers.php     Page / form handlers
lib/supabase.php     Supabase REST + Storage client
lib/vcard.php        vCard 3.0 (photo ကို base64 နဲ့ ထည့်ထားတယ်)
lib/views/*.php      Templates
assets/              CSS, image resize JS, QR designer JS
supabase/schema.sql  Database + storage bucket
```

## Notes
- ပုံတွေကို upload မလုပ်ခင် browser ထဲမှာ resize လုပ်ထားတယ် (photo 480px, logo 600px)။ ဒါကြောင့် .vcf file သေးပြီး မြန်တယ်။
- Public card တွေကို Vercel edge မှာ 60 စက္ကန့် cache လုပ်ထားလို့ admin မှာ ပြင်ပြီး ၁ မိနစ်အတွင်း ပြောင်းသွားပါမယ်။
- Staff ကို **Inactive** လုပ်လိုက်ရင် သူ့ card နဲ့ QR အလုပ်မလုပ်တော့ပါဘူး (ဥပမာ အလုပ်ထွက်သွားရင်)။
- Card link (slug) ကို ပြောင်းလိုက်ရင် print ထုတ်ပြီးသား QR တွေ အလုပ်မလုပ်တော့ပါဘူး။
