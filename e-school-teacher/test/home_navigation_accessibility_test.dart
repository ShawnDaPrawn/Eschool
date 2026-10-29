import 'dart:io';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/widgets/homeContainerExploreAcademicsItemContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

class _Labels extends AppLocalization {
  _Labels() : super(const Locale('en'));
  @override
  String? getTranslatedValues(String? key) => key;
}

class _LabelsDelegate extends LocalizationsDelegate<AppLocalization> {
  const _LabelsDelegate();
  @override
  bool isSupported(Locale locale) => true;
  @override
  Future<AppLocalization> load(Locale locale) => SynchronousFuture(_Labels());
  @override
  bool shouldReload(LocalizationsDelegate<AppLocalization> old) => false;
}

void main() {
  for (final width in [320.0, 360.0, 1280.0]) {
    for (final scale in [1.0, 2.0, 3.0]) {
      testWidgets(
        'Teacher actions fit ${width}px at ${scale * 100}% text and remain usable',
        (tester) async {
          tester.view.physicalSize = Size(width, 800);
          tester.view.devicePixelRatio = 1;
          addTearDown(tester.view.reset);
          final loader = FontLoader('NavigationPoppins')
            ..addFont(
              Future.value(
                ByteData.sublistView(
                  File('google_fonts/Poppins-Medium.ttf').readAsBytesSync(),
                ),
              ),
            );
          await loader.load();
          final labels = [
            'Polls, surveys and elections',
            'Incidents and counselling',
            'Meningspeilings, opnames en verkiesings',
            'Izinhlolovo nokhetho',
          ];
          await tester.pumpWidget(
            MaterialApp(
              theme: AppTheme.light().copyWith(
                textTheme: ThemeData.light().textTheme.apply(
                  fontFamily: 'NavigationPoppins',
                ),
              ),
              localizationsDelegates: const [_LabelsDelegate()],
              builder: (context, child) => MediaQuery(
                data: MediaQuery.of(context)
                    .copyWith(textScaler: TextScaler.linear(scale)),
                child: child!,
              ),
              routes: {
                '/destination': (_) =>
                    const Scaffold(body: Text('Opened destination')),
              },
              home: Scaffold(
                body: SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: ExploreAcademicsGrid(
                    items: [
                      for (final label in labels)
                        MenuContainerDetails(
                          iconPath: Assets.resultIcon,
                          titleKey: label,
                          route: '/destination',
                        ),
                    ],
                  ),
                ),
              ),
            ),
          );
          await tester.pumpAndSettle();
          expect(tester.takeException(), isNull);
          expect(find.byType(FilledButton), findsNWidgets(labels.length));
          // Text is allowed its full system scale and height, including the last
          // row outside the initial viewport; the destination remains reachable.
          final last = find.text(labels.last);
          await tester.ensureVisible(last);
          await tester.tap(last);
          await tester.pumpAndSettle();
          expect(find.text('Opened destination'), findsOneWidget);
          expect(tester.takeException(), isNull);
        },
      );
    }
  }
}
