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
# Keep the Vue homepage and its advertisements mounted while only draw panels switch.
patch('index-5ZnwG-jb.js', 'const s=y(!0),a=D(()=>c.ad.length>0);return A(function(){c.init()})',
      'let portal;const s=y(!0),a=D(()=>c.ad.length>0);ie(()=>portal?.destroy());return A(function(){portal=mountPortal(c)})')
patch('index-5ZnwG-jb.js', 'var T=',
      'import{mountPortal}from"../js/portal-reference-bridge.js?v=20261019-9";var T=')
patch('index-5ZnwG-jb.js', 'ys={class:"flex flex-col pb-2"}',
      'ys={id:"portal-home",class:"flex flex-col pb-2"}')
patch('index-5ZnwG-jb.js', 'r(c).is_init?(i(),G(Vt,{key:1})):w("",!0)',
      'e("nav",{class:"portal-game-nav","aria-label":"彩种切换",innerHTML:window.portalGameLinks||""}),'
      'e("p",{id:"portal-switch-status",role:"status",hidden:true}),'
      'e("p",{id:"portal-shared-status",role:"status","aria-live":"polite",hidden:true}),'
      'e("div",{id:"portal-current-host",class:"multi-portal portal-game-embedded",hidden:r(c).selected_game==="jnd28",innerHTML:window.portalGameCurrent||""},null,8,["hidden"]),'
      'r(c).is_init&&r(c).selected_game==="jnd28"?(i(),G(Vt,{key:1})):w("",!0)')
patch('index-5ZnwG-jb.js', 'S(us)])]),S(_,{to:"/notice"',
      'e("div",{id:"portal-history-host",class:"multi-portal portal-game-embedded",hidden:r(c).selected_game==="jnd28",innerHTML:window.portalGameHistory||""},null,8,["hidden"]),'
      'r(c).selected_game==="jnd28"?S(us):w("",!0),'
      'e("div",{id:"portal-pagination-host",innerHTML:window.portalHistoryPagination||""})])]),S(_,{to:"/notice"')
# The live header keeps polling; historical pages have an independent data owner.
patch('website-BTB8_uk9.js', 'o.result=s,o.prediction=',
      'o.history_locked||(o.result=s),o.history_refresh?.(i),o.prediction=')
# Do not prepend the next live issue to an older historical page.
patch('index-5ZnwG-jb.js', 'e("tbody",null,[e("tr",null,[e("td",null,d((k=r(c).prediction_period)',
      'e("tbody",null,[e("tr",{class:"portal-live-issue"},[e("td",null,d((k=r(c).prediction_period)')
patch('index-5ZnwG-jb.js', 'e("tbody",null,[e("tr",null,[e("td",null,[e("div",An,d(r(c).prediction_period)',
      'e("tbody",null,[e("tr",{class:"portal-live-issue"},[e("td",null,[e("div",An,d(r(c).prediction_period)')
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
patch('website-BTB8_uk9.js', 'lottery_id:1,timer:!1', 'lottery_id:1,timer:!1,pending:!1,portal_active:!1,selected_game:window.portalInitialGame||"jnd28"')
patch('website-BTB8_uk9.js', 'website:"",entry:""', 'website:window.portalWebsite||"",entry:""')
patch('website-BTB8_uk9.js', 'open:function(e=!1){return h(this,null,function*(){',
      'open:function(e=!1){return h(this,null,function*(){if(o.pending||!o.portal_active||o.selected_game!=="jnd28")return;')
patch('website-BTB8_uk9.js', 'const t=yield x({api:O.website_open,data:{id:o.lottery_id}});',
      'o.pending=!0;let t;try{t=yield x({api:O.website_open,data:{id:o.lottery_id}})}catch{if(o.portal_active&&o.selected_game==="jnd28")o.portal_error?.("开奖数据暂时不可用，正在自动重试…");return;}finally{o.pending=!1;e&&de()}if(!o.portal_active||o.selected_game!=="jnd28")return;o.portal_error?.(t?.code===200?"":"开奖数据暂时不可用，正在自动重试…");')
patch('website-BTB8_uk9.js', 'yield o.open(e),o.timer===!1&&',
      'yield o.open(e),o.portal_active&&o.selected_game==="jnd28"&&o.timer===!1&&')
patch('website-BTB8_uk9.js', 'document.title=t.title',
      'void 0')
# Configuration retries must neither block the public draw panel nor restart its poller.
# The pinned snapshot stays unchanged; this adapter owns the local loading lifecycle.
website_apply = (
    'o.ad_ww=t.ad_ww,o.ad_wx=t.ad_wx,o.ad_icon=t.ad_icon,'
    'o.ad=Array.isArray(t.ad)?t.ad.filter(n=>n&&n.cover):[],'
    'o.landing=t.landing,o.entry=t.entry,o.website=t.website,o.icon=t.icon,'
    'o.banner=t.banner,o.show_qrcode=t.show_qrcode,o.notice_scroll=t.notice_scroll'
)
patch('website-BTB8_uk9.js',
      'init:function(){return h(this,null,function*(){const e=yield x({api:O.website_index,data:{id:o.lottery_id}});'
      'if(e.code===200){const t=e.data;' + website_apply + ',void 0}o.is_init=!0,yield this.cycle(!0)})}',
      'init:function(){o.is_init=!0;return portalConfiguration.load()}')
patch('website-BTB8_uk9.js', 'var h=',
      'import{WebsiteConfiguration}from"../js/portal-website-configuration.js?v=20261019-9";var h=')
patch('website-BTB8_uk9.js', 'export{o as W};',
      'const portalConfiguration=new WebsiteConfiguration({'
      'request:()=>x({api:O.website_index,data:{id:o.lottery_id}}),'
      'apply:function(t){' + website_apply + '}});export{o as W};')
# The reference contact page incorrectly uses a website URL as an image source.
# Keep its full-width image layout, bind the uploaded contact icon and real link.
patch('index-Dd6rjNuL.js',
      'o(e).ad_wx?(r(),t("img",{key:0,class:"w-[100%]",src:o(e).ad_wx,alt:"wx"},null,8,i))',
      '(o(e).ad_wx||o(e).ad_ww)?(r(),t("a",{key:0,href:o(e).ad_wx||o(e).ad_ww,target:"_blank",rel:"noopener noreferrer"},[t("img",{class:"w-[100%]",src:o(e).ad_icon||o(e).icon,alt:"wx"},null,8,i)],8,["href"]))')
for name in ['cover.png', 'open.png']:
    shutil.copy2(SOURCE / name, ROOT / 'web' / name)
# Version the entire import graph, including Vite preloads and circular Vue imports.
# Revalidating only the entry URL can leave stale chunks or mount two Vue runtimes.
version = '20261019-9'
scripts = list(OUTPUT.glob('*.js'))
for script in scripts:
    contents = script.read_text('utf-8')
    for dependency in scripts:
        contents = contents.replace(dependency.name + '"', dependency.name + '?v=' + version + '"')
    script.write_text(contents, 'utf-8')
print('Built reference UI with verified source checksums.')
