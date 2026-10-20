import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/feesReceiptCubit.dart';
import 'package:eschool/data/models/paidFees.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ListItemForFeesStatus extends StatelessWidget {

  const ListItemForFeesStatus({
    required this.className, required this.status, required this.year, required this.fileNamePrefix, required this.fees, required this.receiptProcessing, super.key,
    this.onItemTap,
    this.id,
  });
  final String className;
  final int status;
  final String year;
  final VoidCallback? onItemTap;
  final int? id;
  final bool receiptProcessing;
  final String fileNamePrefix;
  final List<PaidFees> fees;

  Widget _buildDetailsBackgroundContainer({
    required Widget child,
    required BuildContext context,
  }) {
    return Center(
      child: Container(
        margin: const EdgeInsets.only(bottom: 25),
        width: MediaQuery.sizeOf(context).width * 0.90,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(10),
        ),
        child: child,
      ),
    );
  }

  TextStyle _getLabelTextStyle({required BuildContext context}) {
    return TextStyle(
      color: Theme.of(context).colorScheme.onSurface,
      fontSize: 12,
      fontWeight: FontWeight.w400,
    );
  }

  Widget _buildLabelAndValueContainer({
    required BuildContext context,
    required String element2, String element1 = '',
    String element3 = '',
  }) {
    return Row(
      children: [
        Wrap(
          children: [
            (element1 != '')
                ? _setTitle(contxt: context, titleTxt: element1)
                : const SizedBox.shrink(),
            Text(element2, style: _getLabelTextStyle(context: context)),
          ],
        ),
        (element3 != '') ? const Spacer() : const SizedBox.shrink(),
        (element3 != '')
            ? Text(element3, style: _getLabelTextStyle(context: context))
            : const SizedBox.shrink(),
      ],
    );
  }

  Text _setTitle({required BuildContext contxt, required String titleTxt}) {
    return Text(
      '${UiUtils.getTranslatedLabel(contxt, titleTxt)} : ',
      style: _getLabelTextStyle(context: contxt),
    );
  }

  TextStyle _getStatusLabelStyle({
    required BuildContext context,
    required int statusVal,
  }) {
    return TextStyle(
      color: (statusVal == 0)
          ? Theme.of(context).colorScheme.error
          : (statusVal == 2)
          ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.8)
          : Theme.of(context).colorScheme.primary,
      fontSize: 12,
      fontWeight: FontWeight.w600,
    );
  }

  Widget _buildStatusContainer({
    required String statusVal,
    required BuildContext context,
  }) {
    return Wrap(
      children: [
        _setTitle(contxt: context, titleTxt: statusKey),
        Text(
          statusVal,
          style: _getStatusLabelStyle(context: context, statusVal: status),
        ),
      ],
    );
  }

  Widget _buildReceiptBtn({
    required BuildContext context,
    required VoidCallback openReceipt,
  }) {
    return GestureDetector(
      onTap: openReceipt,
      child: Text(
        UiUtils.getTranslatedLabel(context, receiptKey),
        style: TextStyle(color: Theme.of(context).colorScheme.primary),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onItemTap,
      child: _buildDetailsBackgroundContainer(
        context: context,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildLabelAndValueContainer(
              context: context,
              element1: classKey,
              element2: className,
              element3: year,
            ),
            const SizedBox(height: 5),
            Row(
              children: [
                _buildStatusContainer(
                  statusVal: UiUtils.getTranslatedLabel(
                    context,
                    UiUtils.getStudentFeesStatusKey(status),
                  ),
                  context: context,
                ),
                const Spacer(),
                status == 0
                    ? _buildLabelAndValueContainer(
                        context: context,
                        element1: dueDateKey,
                        element2: UiUtils.formatDate(
                          context
                                  .read<AppConfigurationCubit>()
                                  .getAppConfiguration()
                                  .sessionYear
                                  .feesDueDate ??
                              DateTime.now(),
                        ),
                      )
                    : receiptProcessing
                    ? CustomCircularProgressIndicator(
                        indicatorColor: Theme.of(context).colorScheme.primary,
                        strokeWidth: 1.5,
                        widthAndHeight: 15,
                      )
                    : _buildReceiptBtn(
                        context: context,
                        openReceipt: () async {
                          Future.delayed(Duration.zero, () {
                            if (context.read<FeesReceiptCubit>().state
                                is FeesReceiptDownloadInProgress) {
                              UiUtils.showCustomSnackBar(
                                context: context,
                                errorMessage: UiUtils.getTranslatedLabel(
                                  context,
                                  anotherRecieptDownloadingKey,
                                ),
                                backgroundColor: Theme.of(
                                  context,
                                ).colorScheme.error,
                              );
                            } else {
                              context
                                  .read<FeesReceiptCubit>()
                                  .downloadFeesReceipt(
                                    feesPaidId: id,
                                    receiptList: fees,
                                    fileNamePrefix: fileNamePrefix,
                                  );
                            }
                          });
                        },
                      ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
