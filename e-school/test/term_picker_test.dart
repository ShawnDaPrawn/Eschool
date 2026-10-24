import 'package:eschool/cubits/termsCubit.dart';
import 'package:eschool/data/models/term.dart';
import 'package:eschool/ui/widgets/termFilterBottomsheetContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _AvailableTerms extends TermsCubit {
  _AvailableTerms(List<Term> terms) {
    emit(TermFetchSuccess(terms: terms));
  }
}

class _ReadyLabels extends LocalizationsDelegate<AppLocalization> {
  const _ReadyLabels(this.labels);
  final AppLocalization labels;
  @override
  bool isSupported(Locale locale) => locale == labels.locale;
  @override
  Future<AppLocalization> load(Locale locale) => SynchronousFuture(labels);
  @override
  bool shouldReload(_ReadyLabels old) => old.labels != labels;
}

void main() {
  testWidgets(
    'a previous grade-band term is selectable without a current term',
    (tester) async {
      final previous = Term.fromJson({
        'id': 17,
        'name': 'Term 1',
        'grade_from': 7,
        'grade_to': 12,
      });
      final terms = _AvailableTerms([previous]);
      addTearDown(terms.close);
      Term? picked;
      // Asset decoding may use a real isolate for the complete language file.
      // Await it outside the widget clock before testing the term selector.
      late AppLocalization labels;
      await tester.runAsync(() async {
        labels = await const AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]).load(const Locale('en'));
      });
      await tester.pumpWidget(
        BlocProvider<TermsCubit>.value(
          value: terms,
          child: MaterialApp(
            theme: AppTheme.light(),
            locale: const Locale('en'),
            supportedLocales: const [Locale('en')],
            localizationsDelegates: [_ReadyLabels(labels)],
            home: Scaffold(
              body: Builder(
                builder: (context) => TextButton(
                  onPressed: () async {
                    picked = await showModalBottomSheet<Term>(
                      context: context,
                      builder: (_) => BlocProvider<TermsCubit>.value(
                        value: terms,
                        child: const TermFilterBottomsheetContainer(
                          selectedTerm: null,
                        ),
                      ),
                    );
                  },
                  child: const Text('Choose term'),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Choose term'));
      await tester.pumpAndSettle();
      expect(find.text('Term 1 (7–12)'), findsOneWidget);
      await tester.tap(find.text('Term 1 (7–12)'));
      await tester.pumpAndSettle();
      expect(picked, previous);
      expect(tester.takeException(), isNull);
    },
  );
}
