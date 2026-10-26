# Synthetic INSTREAM protocol fixture ONLY. This is not malware detection.
import socket,struct,threading,datetime,os
s=socket.socket();s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1);s.bind((os.environ.get('PHOTO_SCANNER_BIND','127.0.0.1'),3317));s.listen()
def handle(c):
 def exact(n):
  b=b''
  while len(b)<n:
   p=c.recv(n-len(b))
   if not p:raise EOFError()
   b+=p
  return b
 try:
  command=b''
  while not command.endswith(b'\0'):command+=exact(1)
  if command==b'zVERSION\0':c.sendall(('ClamAV 1.0-synthetic/1/'+datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')+'\0').encode())
  elif command==b'zINSTREAM\0':
   total=0
   while True:
    n=struct.unpack('!I',exact(4))[0]
    if n==0:break
    if n>65536 or total+n>4194304:raise ValueError()
    exact(n);total+=n
   c.sendall(b'stream: OK\0')
 except (EOFError,ValueError,OSError):pass
 finally:c.close()
while True:
 c,_=s.accept();threading.Thread(target=handle,args=(c,),daemon=True).start()
