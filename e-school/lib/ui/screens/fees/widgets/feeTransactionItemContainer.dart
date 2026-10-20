import 'package:eschool/data/models/feesTransaction.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class FeeTransactionItemContainer extends StatelessWidget {
  const FeeTransactionItemContainer({
    required this.transaction,
    this.padding,
    super.key,
  });

  final FeesTransaction transaction;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding:
          padding ??
          EdgeInsets.symmetric(
            vertical: 8,
            horizontal: MediaQuery.sizeOf(context).width * 0.075,
          ),
      child: Container(
        height: 100,
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          color: Theme.of(context).colorScheme.surface,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          transaction.sessionYear.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                      ),
                      Text(
                        ' • ',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.primary,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                      Flexible(
                        child: Text(
                          transaction.fullName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ],
                  ),
                  Expanded(
                    child: Row(
                      children: [
                        Center(
                          child: SvgPicture.asset(
                            transaction.modeImagePath,
                            width: 35,
                            height: 35,
                            colorFilter: ColorFilter.mode(
                              Theme.of(context).colorScheme.primary,
                              BlendMode.srcIn,
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                '${UiUtils.getTranslatedLabel(context, transaction.feeTypeKey)} ${UiUtils.getTranslatedLabel(context, feesKey)}',
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                                style: const TextStyle(fontSize: 12),
                              ),
                              Text(
                                "${UiUtils.getTranslatedLabel(context, transaction.modeKey)} ${UiUtils.getTranslatedLabel(context, paymentKey)}${transaction.mode == 2 ? " (${transaction.paymentGatewayString}) " : ""}",
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                                style: const TextStyle(fontSize: 10),
                              ),
                              if (transaction.date != null)
                                Text(
                                  UiUtils.formatDateAndTime(transaction.date!),
                                  overflow: TextOverflow.ellipsis,
                                  maxLines: 1,
                                  style: const TextStyle(fontSize: 10),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Column(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.only(top: 5, bottom: 5),
                  width: 80,
                  decoration: BoxDecoration(
                    color: transaction.paymentStatus == 1
                        ? greenColor
                        : transaction.paymentStatus == 0
                        ? redColor
                        : orangeColor,
                    borderRadius: BorderRadius.circular(5),
                  ),
                  child: Text(
                    UiUtils.getTranslatedLabel(
                      context,
                      transaction.paymentStatusKey,
                    ),
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                ),
                Text(
                  UiUtils.formatAmount(
                    strVal: transaction.totalAmount.toStringAsFixed(2),
                    context: context,
                  ),
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.primary,
                    fontWeight: FontWeight.w600,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
