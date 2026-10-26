import 'package:web_socket_channel/io.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

WebSocketChannel openLiveSocket(Uri uri, String origin) =>
    IOWebSocketChannel.connect(uri, headers: {'Origin': origin});
