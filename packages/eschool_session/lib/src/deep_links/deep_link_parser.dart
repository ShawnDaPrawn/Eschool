import 'package:equatable/equatable.dart';

/// A screen a link opens: one of the app's existing route names, and the
/// arguments its route expects.
class DeepLinkTarget extends Equatable {
  const DeepLinkTarget(this.routeName, {this.arguments});

  final String routeName;
  final Object? arguments;

  @override
  List<Object?> get props => [routeName, arguments];

  @override
  String toString() => 'DeepLinkTarget($routeName, $arguments)';
}

/// One link pattern. [path] is matched segment by segment; a segment written
/// `:name` matches any value and hands it to [arguments] under that name.
class DeepLinkRoute {
  const DeepLinkRoute(this.path, this.routeName, {this.arguments});

  final String path;
  final String routeName;

  /// Builds the route's arguments from the `:name` values and the query; may
  /// return `null` when the route takes none. Throwing [FormatException]
  /// means the link is not valid for this route.
  final Object? Function(Map<String, String> values, Map<String, String> query)?
  arguments;
}

/// Maps web URLs and app links to the app's route names, so a link to a
/// portal page and the same page in the app land on the same screen.
///
/// Accepted: `https://<host>/<path>` for a host in [hosts] (a web build
/// passes its own origin), the hash form Flutter web uses
/// (`https://<host>/#/<path>`), and `<scheme>://<path>` for a scheme in
/// [schemes]. Unknown hosts, schemes and paths give `null`.
class DeepLinkParser {
  DeepLinkParser({
    required this.routes,
    this.hosts = defaultHosts,
    this.schemes = const {'navigateed'},
  });

  /// The portal hostnames (production and staging), from the Phase 0
  /// decisions.
  static const Set<String> defaultHosts = {
    'app.navigateed.co.za',
    'teach.navigateed.co.za',
    'staging-app.navigateed.co.za',
    'staging-teach.navigateed.co.za',
  };

  final List<DeepLinkRoute> routes;
  final Set<String> hosts;
  final Set<String> schemes;

  DeepLinkTarget? parse(Uri uri) {
    final List<String>? segments = _segments(uri);
    if (segments == null) {
      return null;
    }
    final Map<String, String> query = _query(uri);
    for (final DeepLinkRoute route in routes) {
      final Map<String, String>? values = _match(route.path, segments);
      if (values == null) {
        continue;
      }
      try {
        return DeepLinkTarget(
          route.routeName,
          arguments: route.arguments?.call(values, query),
        );
      } on FormatException {
        return null;
      }
    }
    return null;
  }

  /// The path segments the link names, or `null` when the link is not ours.
  List<String>? _segments(Uri uri) {
    String path;
    if (uri.scheme == 'http' || uri.scheme == 'https') {
      if (!hosts.contains(uri.host.toLowerCase())) {
        return null;
      }
      path = uri.path;
      // Flutter web's default URLs keep the route after a '#'.
      if ((path.isEmpty || path == '/') && uri.fragment.startsWith('/')) {
        path = Uri.parse(uri.fragment).path;
      }
    } else if (schemes.contains(uri.scheme)) {
      // navigateed://notices/3: the host is the first segment.
      path = '/${uri.host}${uri.path}';
    } else {
      return null;
    }
    return path.split('/').where((segment) => segment.isNotEmpty).toList();
  }

  Map<String, String> _query(Uri uri) {
    final Map<String, String> query = Map.of(uri.queryParameters);
    if (uri.fragment.contains('?')) {
      query.addAll(Uri.parse(uri.fragment).queryParameters);
    }
    return query;
  }

  Map<String, String>? _match(String pattern, List<String> segments) {
    final List<String> parts = pattern
        .split('/')
        .where((part) => part.isNotEmpty)
        .toList();
    if (parts.length != segments.length) {
      return null;
    }
    final Map<String, String> values = {};
    for (int i = 0; i < parts.length; i++) {
      if (parts[i].startsWith(':')) {
        values[parts[i].substring(1)] = Uri.decodeComponent(segments[i]);
      } else if (parts[i].toLowerCase() != segments[i].toLowerCase()) {
        return null;
      }
    }
    return values;
  }
}
