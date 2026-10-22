import 'dart:async';

import 'package:app_links/app_links.dart';
import 'package:eschool_session/src/deep_links/deep_link_parser.dart';

/// Where links come from: the link that opened the app (on the web, the
/// address in the browser) and the ones that arrive while it runs.
abstract interface class DeepLinkSource {
  Future<Uri?> initialLink();
  Stream<Uri> get links;
}

/// [DeepLinkSource] through the `app_links` plugin, which reads Android and
/// iOS app links and the browser's address on the web.
class AppLinksDeepLinkSource implements DeepLinkSource {
  AppLinksDeepLinkSource([AppLinks? appLinks])
    : _appLinks = appLinks ?? AppLinks();

  final AppLinks _appLinks;

  @override
  Future<Uri?> initialLink() async {
    try {
      return await _appLinks.getInitialLink();
    } catch (_) {
      return null;
    }
  }

  @override
  Stream<Uri> get links => _appLinks.uriLinkStream;
}

/// Turns incoming links into screens to open. The link that opened the app
/// is kept as [takePending] until the app has passed its splash screen and
/// sign-in; links that arrive later come through [targets].
class DeepLinkCoordinator {
  DeepLinkCoordinator({required this.parser, required DeepLinkSource source})
    : _source = source;

  final DeepLinkParser parser;
  final DeepLinkSource _source;
  final StreamController<DeepLinkTarget> _targets =
      StreamController<DeepLinkTarget>.broadcast();
  StreamSubscription<Uri>? _subscription;
  DeepLinkTarget? _pending;

  Stream<DeepLinkTarget> get targets => _targets.stream;

  Future<void> start() async {
    final Uri? initial = await _source.initialLink();
    if (initial != null) {
      _pending = parser.parse(initial);
    }
    _subscription ??= _source.links.listen((uri) {
      final DeepLinkTarget? target = parser.parse(uri);
      if (target == null) {
        return;
      }
      if (_targets.hasListener) {
        _targets.add(target);
      } else {
        _pending = target;
      }
    }, onError: (_) {});
  }

  /// Puts a link back to wait, for example one that arrived before the user
  /// signed in.
  void keepPending(DeepLinkTarget target) => _pending = target;

  /// The screen a link asked for that nobody has opened yet, once.
  DeepLinkTarget? takePending() {
    final DeepLinkTarget? target = _pending;
    _pending = null;
    return target;
  }

  /// [takePending], but only a link [test] accepts; any other stays
  /// waiting. For links that open before sign-in (an invitation).
  DeepLinkTarget? takePendingWhere(bool Function(DeepLinkTarget target) test) {
    final DeepLinkTarget? target = _pending;
    if (target == null || !test(target)) {
      return null;
    }
    _pending = null;
    return target;
  }

  Future<void> dispose() async {
    await _subscription?.cancel();
    await _targets.close();
  }
}
