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
def check_plugin_dispatch(source):
 assert swift_body(source,'private var isVendorBuild: Bool').strip() == 'Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.vendor"'
 assert swift_body(source,'private var isFacilitatorBuild: Bool').strip() == 'Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.facilitator"'
 plugins=re.sub(r'//[^\n]*','',swift_body(source,'func didInitializeImplicitFlutterEngine(')).strip()
 assert plugins.startswith('if isVendorBuild {')
 vendor_plugins=swift_body(plugins,'if isVendorBuild')
 assert 'GeneratedPluginRegistrant' not in vendor_plugins and 'Firebase' not in vendor_plugins
 assert vendor_plugins.strip().endswith('return')
 assert set(re.findall(r'\b(\w+)\.register\(with: registrar\)',vendor_plugins)) == {'MobileScannerPlugin','SharedPreferencesPlugin'}
 assert set(re.findall(r'registrar\(forPlugin: "([^"]+)"\)',vendor_plugins)) == {'MobileScannerPlugin','SharedPreferencesPlugin','VendorPaymentNfc'}
 assert 'VendorPaymentNfc()' in vendor_plugins and 'vendorPaymentNfc = reader' in vendor_plugins
 assert 'FlutterMethodChannel(name: "navigateed/vendor-payment-nfc"' in vendor_plugins
 # After each selective branch, require its early return and the next exact gate.
 start=plugins.index('{',plugins.index('if isVendorBuild'))
 remainder=plugins[start+len(vendor_plugins)+2:].strip()
 assert remainder.startswith('if isFacilitatorBuild {')
 fac_plugins=swift_body(remainder,'if isFacilitatorBuild')
 assert set(re.findall(r'\b(\w+)\.register\(with: registrar\)',fac_plugins)) == {'URLLauncherPlugin'}
 assert set(re.findall(r'registrar\(forPlugin: "([^"]+)"\)',fac_plugins)) == {'URLLauncherPlugin'}
 assert 'GeneratedPluginRegistrant' not in fac_plugins and 'Firebase' not in fac_plugins
 assert fac_plugins.strip().endswith('return')
 start=remainder.index('{',remainder.index('if isFacilitatorBuild'))
 teacher_plugins=remainder[start+len(fac_plugins)+2:].strip()
 assert ' '.join(teacher_plugins.split()) == 'guard isTeacherBuild else { return } GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)'
 assert source.count('GeneratedPluginRegistrant.register(')==1
 assert source.count('FirebaseApp.configure()')==1
 assert source.count('URLLauncherPlugin.register(')==1

check_plugin_dispatch(launch)
# Reject widening either selective identity or moving Teacher registration into it.
for before,after in [
 ('tech.tokensoft.navigateed.vendor','tech.tokensoft.navigateed.teacher'),
 ('MobileScannerPlugin.register(with: registrar)','GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)'),
 ('MobileScannerPlugin.register(with: registrar)','FirebaseApp.configure(); MobileScannerPlugin.register(with: registrar)'),
 ('MobileScannerPlugin.register(with: registrar)','UnknownPlugin.register(with: registrar); MobileScannerPlugin.register(with: registrar)'),
 ('URLLauncherPlugin.register(with: registrar)','MobileScannerPlugin.register(with: registrar)'),
]:
 assert before in launch
 try:check_plugin_dispatch(launch.replace(before,after))
 except AssertionError:pass
 else:raise AssertionError('Unsafe selective plugin registration was accepted')

# NFC signing authority belongs only to the three signed Vendor configurations.
with (r/'ios/Runner/Vendor.entitlements').open('rb') as f:nfc=plistlib.load(f)
assert nfc=={'com.apple.developer.nfc.readersession.formats':['NDEF']}
for entry in target_entries:
 settings=entry['buildSettings']
 assert (settings.get('CODE_SIGN_ENTITLEMENTS')=='Runner/Vendor.entitlements') == entry['name'].endswith('-vendor')
ns='{http://schemas.android.com/apk/res/android}'
vendor_manifest=ET.parse(r/'android/app/src/vendor/AndroidManifest.xml').getroot()
assert [x.attrib[ns+'name'] for x in vendor_manifest.findall('uses-permission')]==['android.permission.NFC']
assert any(x.attrib.get(ns+'name')=='android.hardware.nfc' and x.attrib.get(ns+'required')=='false' for x in vendor_manifest.findall('uses-feature'))
main_manifest=ET.parse(r/'android/app/src/main/AndroidManifest.xml').getroot()
assert all(x.attrib[ns+'name']!='android.permission.NFC' for x in main_manifest.findall('uses-permission'))

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
