import 'package:eschool_teacher/cubits/appSettingsCubit.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';

class AppSettingsBlocBuilder extends StatelessWidget {

  const AppSettingsBlocBuilder({required this.appSettingsType, super.key});
  final String appSettingsType;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AppSettingsCubit, AppSettingsState>(
      builder: (context, state) {
        if (state is AppSettingsFetchSuccess) {
          return SingleChildScrollView(
            padding: EdgeInsets.only(
              top:
                  MediaQuery.sizeOf(context).height *
                  (UiUtils.appBarSmallerHeightPercentage + 0.025),
            ),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Html(data: state.appSettingsResult),
                ),
              ],
            ),
          );
        }
        if (state is AppSettingsFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<AppSettingsCubit>().fetchAppSettings(
                  type: appSettingsType,
                );
              },
            ),
          );
        }
        return Center(
          child: CustomCircularProgressIndicator(
            indicatorColor: Theme.of(context).colorScheme.primary,
          ),
        );
      },
    );
  }
}
