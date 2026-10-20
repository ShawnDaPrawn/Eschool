import 'package:eschool_teacher/cubits/academicCalendarPdfDownloadCubit.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';
import 'package:open_filex/open_filex.dart';

class DownloadAcademicCalendarContainer extends StatelessWidget {
  const DownloadAcademicCalendarContainer({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<
      AcademicCalendarPdfDownloadCubit,
      AcademicCalendarPdfDownloadState
    >(
      listener: (context, state) {
        if (state is AcademicCalendarPdfDownloadSuccess) {
          OpenFilex.open(state.filePath);
        } else if (state is AcademicCalendarPdfDownloadFailure) {
          UiUtils.showBottomToastOverlay(
            context: context,
            errorMessage: UiUtils.getErrorMessageFromErrorCode(
              context,
              state.exception,
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          );
        }
      },
      builder: (context, state) {
        if (state is AcademicCalendarPdfDownloadInProgress) {
          return const SizedBox(
            height: 24,
            width: 24,
            child: CustomCircularProgressIndicator(),
          );
        } else {
          return GestureDetector(
            child: SvgPicture.asset(Assets.downloadIcon, width: 24, height: 24),
            onTap: () {
              context
                  .read<AcademicCalendarPdfDownloadCubit>()
                  .downloadAcademicCalendarPdf(
                    context
                        .read<AppConfigurationCubit>()
                        .getAppConfiguration()
                        .sessionYear
                        .name,
                  );
            },
          );
        }
      },
    );
  }
}
