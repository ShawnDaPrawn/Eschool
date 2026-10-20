import 'package:eschool/cubits/academicCalendarPdfDownloadCubit.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';
import 'package:open_filex/open_filex.dart';

class DownloadAcademicCalendarContainer extends StatelessWidget {
  final int? childId;
  const DownloadAcademicCalendarContainer({super.key, this.childId});

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
          UiUtils.showCustomSnackBar(
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
                    childId: childId?.toString(),
                  );
            },
          );
        }
      },
    );
  }
}
