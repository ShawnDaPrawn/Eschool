#!/usr/bin/env python3
"""Check native configuration references; this does not compile native code."""
import re,json,plistlib,xml.etree.ElementTree as ET
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
 return t[1:-1] if t.startswith('"') else t
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
ET.parse(r/'android/app/src/main/AndroidManifest.xml')
print(json.dumps({'pbx_objects':len(objects),'named_configs':18,'schemes':3,'configuration_lists':2,'pod_mappings':9,'native_metadata_integrity':'PASS','native_build':'NOT RUN: no Android SDK/Xcode'}))
