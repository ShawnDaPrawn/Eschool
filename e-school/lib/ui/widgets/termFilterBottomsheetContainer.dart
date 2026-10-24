import 'package:eschool/cubits/termsCubit.dart';
import 'package:eschool/data/models/term.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TermFilterBottomsheetContainer extends StatefulWidget {
  final Term? selectedTerm;
  const TermFilterBottomsheetContainer({super.key, required this.selectedTerm});

  @override
  State<TermFilterBottomsheetContainer> createState() =>
      _TermFilterBottomsheetContainerState();
}

class _TermFilterBottomsheetContainerState
    extends State<TermFilterBottomsheetContainer> {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
        vertical: MediaQuery.sizeOf(context).height * 0.05,
      ),
      width: MediaQuery.sizeOf(context).width,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
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
            UiUtils.getTranslatedLabel(context, sortByKey),
            style: TextStyle(
              fontSize: 16,
              color: Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.bold,
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Divider(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          BlocBuilder<TermsCubit, TermState>(
            builder: (context, state) {
              if (state is TermFetchSuccess) {
                return Material(
                  color: Theme.of(context).colorScheme.surface,
                  child: RadioGroup<Term>(
                    groupValue: widget.selectedTerm,
                    onChanged: (value) {
                      Navigator.pop(context, value);
                    },
                    child: ListView.builder(
                      shrinkWrap: true,
                      itemCount: state.terms.length,
                      padding: EdgeInsets.zero,
                      itemBuilder: (context, index) {
                        final term = state.terms[index];
                        return RadioListTile<Term>(
                          title: Text(
                            term.displayName,
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.onSurface,
                              fontSize: 16,
                            ),
                          ),
                          value: term,
                          controlAffinity: ListTileControlAffinity.trailing,
                        );
                      },
                    ),
                  ),
                );
              } else if (state is TermFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    errorMessageCode: state.errorMessage,
                    onTapRetry: () {
                      context.read<TermsCubit>().fetchTerms();
                    },
                  ),
                );
              } else {
                return const Center(child: CustomCircularProgressIndicator());
              }
            },
          ),
        ],
      ),
    );
  }
}
