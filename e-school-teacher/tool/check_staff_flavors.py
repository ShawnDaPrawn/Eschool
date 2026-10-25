#!/usr/bin/env python3
"""Check native configuration references; this does not compile native code."""
import re,json,plistlib,subprocess,xml.etree.ElementTree as ET
from pathlib import Path
r=Path(__file__).resolve().parents[1]
s=(r/'ios/Runner.xcodeproj/project.pbxproj').read_text()
tokens=[t for t in re.findall(r'/\*.*?\*/|//[^\n]*|"(?:\\.|[^"\\])*"|[{}()=;,]|[^\s{}()=;,]+',s,re.S) if not t.startswith(('/*','//'))]
pos=0
def take(expected=None):
 global pos
 t=tokens[pos];pos+=1
 if expected is not None:assert t==expected,(t,expected,pos)
 return t
def value():
 t=take()
 if t=='{':
  out={}
  while tokens[pos]!='}':
   k=take();take('=');v=value();take(';');assert k not in out,k;out[k]=v
  take('}');return out
 if t=='(':
  out=[]
  while tokens[pos]!=')':
   out.append(value())
   if tokens[pos]==',':take(',')
  take(')');return out
 return json.loads(t) if t.startswith('"') else t
project=value();assert pos==len(tokens)
objects=project['objects']
for flavor in ['teacher','facilitator','vendor']:
 root=ET.parse(r/f'ios/Runner.xcodeproj/xcshareddata/xcschemes/{flavor}.xcscheme').getroot()
 expected={mode+'-'+flavor for mode in ['Debug','Release','Profile']}
 actual={x.attrib['buildConfiguration'] for x in root if 'buildConfiguration' in x.attrib}
 assert actual==expected,(flavor,actual)
 for conf in ['97C146E91CF9000F007C117D','97C147051CF9000F007C117D']:
  entries=[objects[k] for k in objects[conf]['buildConfigurations']]
  names=[x['name'] for x in entries];assert len(names)==len(set(names))==12
  assert all(('PRODUCT_BUNDLE_IDENTIFIER' in x['buildSettings']) == (conf == '97C147051CF9000F007C117D') for x in entries)
  assert expected.issubset(names)
  for entry in entries:
   if entry['name'] not in expected or conf!='97C147051CF9000F007C117D':continue
   settings=entry['buildSettings']
   assert settings['FLAVOR']==flavor
   assert settings['PRODUCT_BUNDLE_IDENTIFIER']=='tech.tokensoft.navigateed.'+flavor
   assert settings['APP_DISPLAY_NAME']=='NavigateEd '+flavor.title()
   assert settings['INFOPLIST_KEY_CFBundleDisplayName']==settings['APP_DISPLAY_NAME']
   if flavor!='teacher':assert 'GoogleService-Info.plist' in settings['EXCLUDED_SOURCE_FILE_NAMES']
 for action in root.findall('.//BuildableReference'):
  assert action.attrib['BlueprintIdentifier'] in objects
pod=(r/'ios/Podfile').read_text()
for flavor in ['teacher','facilitator','vendor']:
 for mode in ['Debug','Release','Profile']:
  assert f"'{mode}-{flavor}' => :"+('debug' if mode=='Debug' else 'release') in pod
with (r/'ios/Runner/Info.plist').open('rb') as f:info=plistlib.load(f)
assert info['CFBundleDisplayName']=='$(APP_DISPLAY_NAME)'
assert info['CFBundleName']=='$(APP_DISPLAY_NAME)'
assert info['CFBundleIdentifier']=='$(PRODUCT_BUNDLE_IDENTIFIER)'
# Native launch happens before Dart; never rely only on initializeApp's guard.
# Bind the native service allowlist to every target configuration's signed ID.
target_entries=[objects[k] for k in objects['97C147051CF9000F007C117D']['buildConfigurations']]
for entry in target_entries:
 settings=entry['buildSettings']
 name=entry['name']
 teacher=name in ['Debug','Release','Profile'] or name.endswith('-teacher')
 assert (settings['PRODUCT_BUNDLE_IDENTIFIER']=='tech.tokensoft.navigateed.teacher') == teacher
 assert ('GoogleService-Info.plist' in settings.get('EXCLUDED_SOURCE_FILE_NAMES','')) == (not teacher)
 assert settings['FLAVOR']==('teacher' if teacher else name.split('-')[-1])

def swift_body(source, marker):
 start=source.index('{',source.index(marker));depth=1;end=start+1
 while depth:
  if source[end]=='{':depth+=1
  if source[end]=='}':depth-=1
  end+=1
 return source[start+1:end-1]

launch=(r/'ios/Runner/AppDelegate.swift').read_text()
assert swift_body(launch,'private var isTeacherBuild: Bool').strip() == 'Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.teacher"'
application=swift_body(launch,'override func application(')
assert application.lstrip().startswith('if isTeacherBuild {')
allowed=swift_body(application,'if isTeacherBuild')
assert 'FirebaseApp.configure()' in allowed
assert 'FirebaseApp.app() == nil' in allowed
assert 'UNUserNotificationCenter.current().delegate = self' in allowed
assert application.count('FirebaseApp.configure()')==allowed.count('FirebaseApp.configure()')==1
assert launch.count('FirebaseApp.configure()')==1
plugins=swift_body(launch,'func didInitializeImplicitFlutterEngine(')
# Ignore comments while verifying the early return precedes any plugin call.
plugins=re.sub(r'//[^\n]*','',plugins).strip()
assert swift_body(launch,'private var isFacilitatorBuild: Bool').strip() == 'Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.facilitator"'
assert plugins.startswith('if isFacilitatorBuild {')
fac_plugins=swift_body(plugins,'if isFacilitatorBuild')
assert 'URLLauncherPlugin.register(with: registrar)' in fac_plugins
assert 'registrar(forPlugin: "URLLauncherPlugin")' in fac_plugins
assert 'GeneratedPluginRegistrant' not in fac_plugins and 'Firebase' not in fac_plugins
assert fac_plugins.strip().endswith('return')
assert launch.count('URLLauncherPlugin.register(')==1
fac_end=plugins.index('guard isTeacherBuild else { return }')
assert plugins[fac_end:].startswith('guard isTeacherBuild else { return }')
assert plugins.count('GeneratedPluginRegistrant.register(')==launch.count('GeneratedPluginRegistrant.register(')==1
assert plugins.index('guard isTeacherBuild') < plugins.index('GeneratedPluginRegistrant.register(')

# Exercise the actual leading shell gate with native build identities. The
# upload command is deliberately excluded so this audit can never make a request.
upload=objects['E2C5428594F83637CB53CDC7']['shellScript']
gate=upload[:upload.index('# Retained compilation artifacts')]
assert 'PRODUCT_BUNDLE_IDENTIFIER' in gate
assert upload.index('PRODUCT_BUNDLE_IDENTIFIER') < upload.index('flutterfire upload-crashlytics-symbols --')
subprocess.run(['/bin/sh','-n','-c',upload],check=True)
identities=[x['buildSettings']['PRODUCT_BUNDLE_IDENTIFIER'] for x in target_entries]+['','unknown.staff.identity']
for identity in identities:
 result=subprocess.run(['/bin/sh','-c',gate+"printf 'STAFF_AUDIT_REACHED'"],env={'PRODUCT_BUNDLE_IDENTIFIER':identity},capture_output=True,text=True,check=True)
 assert (result.stdout=='STAFF_AUDIT_REACHED') == (identity=='tech.tokensoft.navigateed.teacher'),identity
ET.parse(r/'android/app/src/main/AndroidManifest.xml')
print(json.dumps({'pbx_objects':len(objects),'named_configs':18,'schemes':3,'configuration_lists':2,'pod_mappings':9,'native_metadata_integrity':'PASS','native_startup_guard_wiring':'PASS','crashlytics_shell_gate_cases':len(identities),'native_build':'NOT RUN: no Android SDK/Xcode'}))
