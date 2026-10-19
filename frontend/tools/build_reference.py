"""Build pinned reference UI; adapt data transport and unavailable states only."""
from pathlib import Path
import hashlib
import json
import shutil

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'resources' / 'reference'
OUTPUT = ROOT / 'web' / 'assets1'
OUTPUT.mkdir(parents=True, exist_ok=True)

for name, checksum in json.loads((SOURCE / 'manifest.json').read_text('utf-8')).items():
    data = (SOURCE / name).read_bytes()
    if hashlib.sha256(data).hexdigest() != checksum:
        raise RuntimeError('Reference source checksum mismatch: ' + name)
    shutil.copy2(SOURCE / name, OUTPUT / name)

def patch(name, before, after, count=1):
    path = OUTPUT / name
    text = path.read_text('utf-8')
    if text.count(before) != count:
        raise RuntimeError('Unexpected patch count: ' + name + ' ' + before[:60])
    path.write_text(text.replace(before, after), 'utf-8')

patch('index-zEjWpYFa.js', 'history:du(),routes:Vu',
      'history:du(location.pathname+location.search+"#"),routes:Vu')
patch('index-DKj2nBsJ.js',
      'url:Wa.config.apiBaseURL+r.api,method:r.method,data:r.data,headers:r.headers',
      'url:window.portalApiRoutes[r.api],method:"GET",params:r.data,headers:r.headers')
patch('index-5ZnwG-jb.js',
      '`${r(ue).config.apiBaseURL}/index/website/qrcode`', 'window.portalQrcode')
# Keep the game selector outside the collapsible ads, immediately before results.
# Links are server-rendered with Html::encode / Url::to and JSON-escaped in the shell.
patch('index-5ZnwG-jb.js', 'r(c).is_init?(i(),G(Vt,{key:1}))',
      'e("nav",{class:"portal-game-nav","aria-label":"彩种切换",innerHTML:window.portalGameLinks||""}),r(c).is_init?(i(),G(Vt,{key:1}))')
patch('index-5ZnwG-jb.js', 'filter:{type:"result"}',
      'filter:{type:["result","trend","statistics","prediction"].includes(new URLSearchParams(location.hash.split("?")[1]||"").get("tab"))?new URLSearchParams(location.hash.split("?")[1]||"").get("tab"):"result"}')
# Preserve compiled Vue block structure; only change text bindings and their flags.
for variable in ['ns', 'ls', 'as', 'is']:
    patch('index-5ZnwG-jb.js', 'u("span",'+variable+',"❌")',
          'u("span",'+variable+',l.prediction_available?"❌":"-",1)')
for expression in ['pdx==="大"?"小":"大"', 'pds==="单"?"双":"单"']:
    patch('index-5ZnwG-jb.js', 'd(l.'+expression+')',
          'd(l.prediction_available?(l.'+expression+'):"-")', 2)
    patch('index-5ZnwG-jb.js', 'd(r(c).prediction.'+expression+')',
          'd(r(c).prediction_available?(r(c).prediction.'+expression+'):"-")')
patch('website-BTB8_uk9.js', 'prediction:{},prediction_period:""',
      'prediction:{},prediction_available:!1,prediction_period:""')
patch('website-BTB8_uk9.js', 'o.prediction=i.prediction,',
      'o.prediction=i.prediction,o.prediction_available=!!i.prediction_available,')
patch('website-BTB8_uk9.js',
      'period:{cperiod:"3148297",early_close:35,end_time:-2,next_time_end:"2024-06-06 19:43:30",next_time_start:"2024-06-06 19:40:00",open_time:33,period:3148298,time:"2024-06-06 19:42:57"}',
      'period:{cperiod:"-",open_time:0,period:"-",time:""}')
patch('website-BTB8_uk9.js', 'const f=s[0];f&&(o.current=',
      'const f=s[0]||{code_arr:[],sum:"",dx:"",ds:""};f&&(o.current=')
patch('website-BTB8_uk9.js', 'lottery_id:1,timer:!1', 'lottery_id:1,timer:!1,pending:!1')
patch('website-BTB8_uk9.js', 'website:"",entry:""', 'website:window.portalWebsite||"",entry:""')
patch('website-BTB8_uk9.js', 'open:function(e=!1){return h(this,null,function*(){',
      'open:function(e=!1){return h(this,null,function*(){if(o.pending)return;')
patch('website-BTB8_uk9.js', 'const t=yield x({api:O.website_open,data:{id:o.lottery_id}});',
      'o.pending=!0;const t=yield x({api:O.website_open,data:{id:o.lottery_id}});o.pending=!1;')
# The reference contact page incorrectly uses a website URL as an image source.
# Keep its full-width image layout, bind the uploaded contact icon and real link.
patch('index-Dd6rjNuL.js',
      'o(e).ad_wx?(r(),t("img",{key:0,class:"w-[100%]",src:o(e).ad_wx,alt:"wx"},null,8,i))',
      '(o(e).ad_wx||o(e).ad_ww)?(r(),t("a",{key:0,href:o(e).ad_wx||o(e).ad_ww,target:"_blank",rel:"noopener noreferrer"},[t("img",{class:"w-[100%]",src:o(e).ad_icon||o(e).icon,alt:"wx"},null,8,i)],8,["href"]))')
for name in ['cover.png', 'open.png']:
    shutil.copy2(SOURCE / name, ROOT / 'web' / name)
print('Built reference UI with verified source checksums.')
