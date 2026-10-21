import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

class ForceUpdateDialogContainer extends StatelessWidget {
  const ForceUpdateDialogContainer({super.key});

  Widget _buildUpdateButton(BuildContext context) {
    return TextButton(
      child: Text(UiUtils.getTranslatedLabel(context, updateKey)),
      onPressed: () async {
        final appUrl = context.read<AppConfigurationCubit>().getAppLink();
        if (await canLaunchUrl(Uri.parse(appUrl))) {
          launchUrl(Uri.parse(appUrl), mode: LaunchMode.externalApplication);
        }
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.scrim.withValues(alpha: 0.5),
      width: MediaQuery.sizeOf(context).width,
      height: MediaQuery.sizeOf(context).height,
      child: Center(
        child: AlertDialog(
          title: Text(UiUtils.getTranslatedLabel(context, updateAvailableKey)),
          content: Text(
            UiUtils.getTranslatedLabel(context, newUpdateAvailableKey),
            style: const TextStyle(fontSize: 14),
          ),
          actions: [_buildUpdateButton(context)],
        ),
      ),
    );
  }
}
