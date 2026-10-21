import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

// A picture of the theme and the shared widgets, compared pixel for pixel.
// Text is drawn in the test font (blocks), so this checks colour, shape and
// size, not typography.
//
// After an intended visual change, run
//   flutter test --update-goldens test/goldens
// and look at the new images before committing them.
void main() {
  testWidgets('theme and shared widgets', (tester) async {
    tester.view.physicalSize = const Size(420, 980);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        home: const Scaffold(body: _Gallery()),
      ),
    );

    await expectLater(
      find.byType(_Gallery),
      matchesGoldenFile('components.png'),
    );
  });
}

class _Gallery extends StatelessWidget {
  const _Gallery();

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final status = StatusColors.of(context);

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        spacing: 12,
        children: [
          _Swatches([
            scheme.primary,
            scheme.onPrimary,
            scheme.primaryContainer,
            scheme.secondary,
            scheme.secondaryContainer,
            scheme.tertiary,
            scheme.tertiaryContainer,
            scheme.error,
          ]),
          _Swatches([
            scheme.surface,
            scheme.surfaceContainerLowest,
            scheme.surfaceContainerLow,
            scheme.surfaceContainerHighest,
            scheme.onSurface,
            scheme.onSurfaceVariant,
            scheme.outline,
            scheme.outlineVariant,
          ]),
          _Swatches([
            status.success,
            status.warning,
            status.danger,
            status.info,
          ]),
          CustomRoundedButton(
            backgroundColor: scheme.primary,
            buttonTitle: 'Save',
            showBorder: false,
            onTap: () {},
          ),
          Row(
            spacing: 12,
            children: [
              Expanded(
                child: CustomRoundedButton(
                  backgroundColor: scheme.surface,
                  titleColor: scheme.primary,
                  borderColor: scheme.primary,
                  buttonTitle: 'Cancel',
                  showBorder: true,
                  height: 45,
                  onTap: () {},
                ),
              ),
              Expanded(
                child: CustomRoundedButton(
                  backgroundColor: scheme.primary,
                  buttonTitle: null,
                  showBorder: false,
                  height: 45,
                  child: const CustomCircularProgressIndicator(
                    widthAndHeight: 20,
                  ),
                ),
              ),
              CustomRoundedButton(
                backgroundColor: status.success,
                titleColor: status.onSuccess,
                buttonTitle: 'Paid',
                showBorder: false,
                height: 30,
                textSize: 12,
                alignment: null,
                padding: const EdgeInsets.symmetric(horizontal: 10),
              ),
            ],
          ),
          Wrap(
            spacing: 8,
            children: [
              FilledButton(onPressed: () {}, child: const Text('Fill')),
              OutlinedButton(onPressed: () {}, child: const Text('Line')),
              TextButton(onPressed: () {}, child: const Text('Text')),
            ],
          ),
          const TextField(decoration: InputDecoration(hintText: 'Hint')),
          const TextField(
            decoration: InputDecoration(labelText: 'Label', errorText: 'Error'),
          ),
          Row(
            spacing: 12,
            children: [
              Switch(value: true, onChanged: (_) {}),
              Switch(value: false, onChanged: (_) {}),
              Checkbox(value: true, onChanged: (_) {}),
              Checkbox(value: false, onChanged: (_) {}),
              const CustomRadioContainer(isSelected: true),
              const CustomRadioContainer(isSelected: false),
              CustomCloseButton(onTapCloseButton: () {}),
            ],
          ),
          Row(
            spacing: 12,
            children: [
              FloatingActionButton(
                onPressed: () {},
                child: const Icon(Icons.add),
              ),
              const Expanded(
                child: Card(
                  child: ListTile(
                    title: Text('Card title'),
                    subtitle: Text('Subtitle'),
                  ),
                ),
              ),
            ],
          ),
          BottomsheetAddFilesDottedBorderContainer(
            onTap: () {},
            title: 'Add files',
          ),
          Container(
            height: 70,
            decoration: BoxDecoration(
              color: scheme.surfaceContainerLow,
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(AppTheme.bottomSheetRadius),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Swatches extends StatelessWidget {
  const _Swatches(this.colors);
  final List<Color> colors;

  @override
  Widget build(BuildContext context) {
    final outline = Theme.of(context).colorScheme.outlineVariant;
    return Row(
      spacing: 4,
      children: [
        for (final color in colors)
          Expanded(
            child: Container(
              height: 28,
              decoration: BoxDecoration(
                color: color,
                border: Border.all(color: outline),
              ),
            ),
          ),
      ],
    );
  }
}
