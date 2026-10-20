import 'package:eschool_teacher/cubits/appLocalizationCubit.dart';
import 'package:eschool_teacher/data/models/appLanguage.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChangeLanguageBottomsheetContainer extends StatelessWidget {
  const ChangeLanguageBottomsheetContainer({super.key});

  Widget _buildAppLanguageTile({
    required AppLanguage appLanguage,
    required BuildContext context,
    required Locale selectedLanguage,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: GestureDetector(
        onTap: () {
          context.read<AppLocalizationCubit>().changeLanguage(
            appLanguage.languageCode,
          );
        },
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(2),
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Theme.of(context).colorScheme.primary,
                  width: 1.75,
                ),
              ),
              child: Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color:
                      UiUtils.getLocaleFromLanguageCode(
                            appLanguage.languageCode,
                          ) ==
                          selectedLanguage
                      ? Theme.of(context).colorScheme.primary
                      : Theme.of(context).scaffoldBackgroundColor,
                ),
              ),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: Text(
                appLanguage.languageName,
                style: TextStyle(
                  fontSize: 14,
                  color: Theme.of(context).colorScheme.secondary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
        vertical: MediaQuery.sizeOf(context).height * 0.05,
      ),
      width: MediaQuery.sizeOf(context).width,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, appLanguageKey),
            style: TextStyle(
              fontSize: 16,
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.bold,
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Divider(color: Theme.of(context).colorScheme.onSurface),
          ),
          BlocBuilder<AppLocalizationCubit, AppLocalizationState>(
            builder: (context, state) {
              return Column(
                children: appLanguages
                    .map(
                      (appLanguage) => _buildAppLanguageTile(
                        appLanguage: appLanguage,
                        context: context,
                        selectedLanguage: state.language,
                      ),
                    )
                    .toList(),
              );
            },
          ),
        ],
      ),
    );
  }
}
