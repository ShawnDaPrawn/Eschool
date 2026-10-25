import 'dart:convert';
import 'dart:io';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/src/localization/academic_text.dart';
import 'package:eschool_academics/src/marks/academic_labels.dart';

class _LoadedLabels extends LocalizationsDelegate<AppLocalization> {
  const _LoadedLabels(this.value);
  final AppLocalization value;
  @override
  bool isSupported(Locale locale) => locale == value.locale;
  @override
  Future<AppLocalization> load(Locale locale) => SynchronousFuture(value);
  @override
  bool shouldReload(_LoadedLabels old) => old.value != value;
}

void main() {
  const languages = [
    AppLanguage(languageCode: 'af', languageName: 'Afrikaans'),
    AppLanguage(languageCode: 'zu', languageName: 'isiZulu'),
  ];
  for (final code in ['af', 'zu']) {
    testWidgets('shared static and mark labels use the actual $code asset', (
      tester,
    ) async {
      final asset = 'assets/languages/$code.json';
      rootBundle.evict(asset);
      tester.binding.defaultBinaryMessenger.setMockMessageHandler(
        'flutter/assets',
        (message) async {
          final key = const StringCodec().decodeMessage(message);
          if (key != asset) return null;
          return ByteData.sublistView(
            File('../../../e-school/$asset').readAsBytesSync(),
          );
        },
      );
      addTearDown(() {
        rootBundle.evict(asset);
        tester.binding.defaultBinaryMessenger.setMockMessageHandler(
          'flutter/assets',
          null,
        );
      });
      final labels =
          jsonDecode(File('../../../e-school/$asset').readAsStringSync())
              as Map<String, dynamic>;
      late AppLocalization loaded;
      await tester.runAsync(() async {
        loaded = await const AppLocalizationDelegate(
          languages,
        ).load(Locale(code));
      });
      await tester.pumpWidget(
        Localizations(
          locale: Locale(code),
          delegates: [
            DefaultWidgetsLocalizations.delegate,
            _LoadedLabels(loaded),
          ],
          child: Directionality(
            textDirection: TextDirection.ltr,
            child: Material(
              child: Column(
                children: [
                  const AcademicText('Save marks'),
                  const AcademicText('School messages'),
                  const AcademicText('Mastery map'),
                  Builder(
                    builder: (context) =>
                        Text(academicLabel(context, 'learner')),
                  ),
                  const AcademicText('Uncatalogued synthetic text'),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text(labels['Save marks']), findsOneWidget);
      expect(find.text(labels['School messages']), findsOneWidget);
      expect(find.text(labels['Mastery map']), findsOneWidget);
      expect(find.text('Mastery map'), findsNothing);
      expect(find.text(labels['Learner']), findsOneWidget);
      expect(find.text('Save marks'), findsNothing);
      expect(find.text('Uncatalogued synthetic text'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
}
