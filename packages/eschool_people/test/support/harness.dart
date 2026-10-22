import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

/// The learner app's English labels for every shared key, so the tests read
/// the words people see.
final Map<String, String> testLabels = () {
  final Map<String, dynamic> english =
      jsonDecode(
            File('../../e-school/assets/languages/en.json').readAsStringSync(),
          )
          as Map<String, dynamic>;
  return {
    for (final String key in [...sharedLabelKeys, 'defaultErrorMessage'])
      key: '${english[key]}',
  };
}();

const Size phone = Size(390, 844);
const Size desktop = Size(1280, 800);

/// Pumps [child] as the apps run shared screens: the theme, the labels, an
/// [AppShell] and the content column on wide windows.
Future<void> pumpScreen(
  WidgetTester tester,
  Widget child, {
  Size size = phone,
}) async {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  const String key = 'assets/languages/en.json';
  rootBundle.evict(key);
  final TestDefaultBinaryMessenger messenger =
      tester.binding.defaultBinaryMessenger;
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    if (utf8.decode(message!.buffer.asUint8List()) == key) {
      return ByteData.sublistView(utf8.encode(jsonEncode(testLabels)));
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light(),
      locale: const Locale('en'),
      supportedLocales: const [Locale('en')],
      localizationsDelegates: const [
        AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]),
      ],
      builder: (context, navigator) => AppShell(
        errorMessageKeyFromCode: (_) => 'defaultErrorMessage',
        openStudyMaterial: (_, _) {},
        studyMaterialButton: (_, _) => const SizedBox(),
        bottomNavigationHeightPercentage: 0.08,
        child: navigator!,
      ),
      home: ContentColumn(child: child),
    ),
  );
  await tester.pumpAndSettle();
}

/// Answers every request with [status] and [body], and keeps the requests.
class RecordingAdapter implements HttpClientAdapter {
  RecordingAdapter(this.status, this.body);

  final int status;
  final Object body;
  final List<RequestOptions> requests = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      jsonEncode(body),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

/// An [ApiClient] answering from [adapter].
ApiClient clientFor(RecordingAdapter adapter) => ApiClient(
  authToken: () => 'token',
  onUnauthorized: () {},
  createDio: () => Dio()..httpClientAdapter = adapter,
);

/// The v2 envelope around [data].
Map<String, Object?> ok(Object? data) => {
  'error': false,
  'message': '',
  'data': data,
  'code': 200,
};

/// A school as the endpoints send it (the generated SchoolSummary).
Map<String, Object?> school(int id, String name, {String? shortName}) => {
  'id': id,
  'name': name,
  'short_name': shortName,
  'crest_url': null,
};
