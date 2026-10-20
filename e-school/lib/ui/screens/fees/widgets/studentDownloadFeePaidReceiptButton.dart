import 'package:eschool/cubits/feesReceiptCubit.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:open_filex/open_filex.dart';

class StudentDownloadFeePaidReceiptButton extends StatelessWidget {
  const StudentDownloadFeePaidReceiptButton({
    required this.studentDetails,
    super.key,
  });
  final Student studentDetails;

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<FeesReceiptCubit, FeesReceiptState>(
      builder: (context, state) {
        return CustomRoundedButton(
          onTap: () {
            if (context.read<FeesReceiptCubit>().state
                is FeesReceiptDownloadInProgress) {
              return;
            } else {
              context.read<FeesReceiptCubit>().downloadFeesReceipt(
                fileNamePrefix:
                    '${studentDetails.getFullName()}_${classKey}_${studentDetails.classSectionName}',
              );
            }
          },
          widthPercentage: 0.7,
          height: 50,
          textAlign: TextAlign.center,
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          buttonTitle: UiUtils.getTranslatedLabel(
            context,
            downloadFeesReceiptKey,
          ),
          titleColor: UiUtils.getColorScheme(context).primary,
          showBorder: true,
          borderColor: UiUtils.getColorScheme(context).primary,
          child: state is FeesReceiptDownloadInProgress
              ? CustomCircularProgressIndicator(
                  strokeWidth: 2,
                  widthAndHeight: 20,
                  indicatorColor: UiUtils.getColorScheme(context).primary,
                )
              : null,
        );
      },
      listener: (context, state) async {
        if (state is FeesReceiptDownloadSuccess) {
          final String msg =
              '${UiUtils.getTranslatedLabel(context, state.successMessageKey)}\n${studentDetails.getFullName()},$classKey ${studentDetails.classSectionName}';
          UiUtils.showCustomSnackBar(
            context: context,
            errorMessage: msg,
            backgroundColor: Theme.of(context).colorScheme.primary,
          );
          try {
            final fileOpenResult = await OpenFilex.open(state.filePath);
            if (fileOpenResult.type != ResultType.done) {
              UiUtils.showCustomSnackBar(
                context: context,
                errorMessage: UiUtils.getTranslatedLabel(
                  context,
                  unableToOpenKey,
                ),
                backgroundColor: Theme.of(context).colorScheme.error,
              );
            }
          } catch (_) {
            if (context.mounted) {
              UiUtils.showCustomSnackBar(
                context: context,
                errorMessage: UiUtils.getTranslatedLabel(
                  context,
                  unableToOpenKey,
                ),
                backgroundColor: errorColor,
              );
            }
          }
        }
        if (state is FeesReceiptDownloadFailure) {
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
    );
  }
}
