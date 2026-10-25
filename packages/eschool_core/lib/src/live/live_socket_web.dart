import 'package:web_socket_channel/html.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

// The browser supplies its actual Origin; scripts cannot override it.
WebSocketChannel openLiveSocket(Uri uri, String origin) =>
    HtmlWebSocketChannel.connect(uri);
