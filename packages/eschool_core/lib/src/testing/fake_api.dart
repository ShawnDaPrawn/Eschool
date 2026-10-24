import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

/// What [FakeApi] answers for one request.
class FakeResponse {
  const FakeResponse(this.body, {this.status = 200});

  /// The answer of a server that has no such route.
  const FakeResponse.notFound()
    : body = const {'message': 'Not Found.'},
      status = 404;

  /// Encoded as JSON.
  final Object? body;
  final int status;
}

/// Synthetic authenticated file response for browser/native integration flows.
class FakeBinaryResponse {
  const FakeBinaryResponse(
    this.bytes, {
    this.contentType = 'application/pdf',
    this.status = 200,
  });
  final List<int> bytes;
  final String contentType;
  final int status;
}

/// Answers every API call from fixtures, with no server and no network: the
/// integration smoke flows of both apps run against it, on a phone and in a
/// browser. Put it in the app's Dio with `Dio()..httpClientAdapter = api`.
///
/// [routes] are keyed by the path below `/api/` (`student/login`,
/// `v2/me/context`), optionally after the method (`POST student/login`),
/// which wins over the bare path. A value is the JSON body to answer with,
/// a [FakeResponse], or a function from the request to either. A path with
/// no route gets [fallback]: the API's "nothing here" answer, an empty
/// list.
class FakeApi implements HttpClientAdapter {
  FakeApi({
    required this.routes,
    this.fallback = const {'error': false, 'message': '', 'data': <Object>[]},
  });

  final Map<String, Object> routes;
  final Object fallback;

  /// Every request so far, oldest first.
  final List<RequestOptions> requests = [];

  /// The paths requested so far, as [routes] keys them with the method:
  /// `GET settings`, `POST student/login`.
  List<String> get requestedPaths => [
    for (final RequestOptions request in requests)
      '${request.method} ${pathOf(request.uri)}',
  ];

  /// Whether a request for [route] (`POST student/login`, or a bare path
  /// for any method) was made.
  bool requested(String route) => requestedPaths.any(
    (requested) => requested == route || requested.endsWith(' $route'),
  );

  /// [uri]'s path below `/api/`, which is how [routes] are keyed.
  static String pathOf(Uri uri) {
    final String path = uri.path;
    const String prefix = '/api/';
    final int start = path.indexOf(prefix);
    return start < 0 ? path : path.substring(start + prefix.length);
  }

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    await requestStream?.drain<void>();
    final String path = pathOf(options.uri);
    Object answer =
        routes['${options.method} $path'] ?? routes[path] ?? fallback;
    if (answer is Function) {
      answer = (answer as Object Function(RequestOptions))(options);
    }
    if (answer is FakeBinaryResponse) {
      return ResponseBody.fromBytes(
        answer.bytes,
        answer.status,
        headers: {
          Headers.contentTypeHeader: [answer.contentType],
        },
      );
    }
    final FakeResponse response = answer is FakeResponse
        ? answer
        : FakeResponse(answer);
    return ResponseBody.fromString(
      jsonEncode(response.body),
      response.status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}
