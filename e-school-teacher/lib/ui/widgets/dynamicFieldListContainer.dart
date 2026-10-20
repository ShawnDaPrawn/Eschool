import 'package:eschool_teacher/data/models/dynamicField.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_teacher/utils/extensions.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class DynamicFieldListContainer extends StatelessWidget {
  const DynamicFieldListContainer({required this.dynamicFields, super.key});
  final List<DynamicFieldModel> dynamicFields;

  Widget _buildDynamicFieldDetailWidget({
    required DynamicFieldModel fieldData,
    required BuildContext context,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14, top: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            fieldData.titleDisplay,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.75),
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 5),
          if (fieldData.isFile)
            GestureDetector(
              onTap: () {
                final file = StudyMaterial(
                  studyMaterialType: StudyMaterialType.file,
                  id: 0,
                  fileName: fieldData.value.split('/').lastOrNull ?? '',
                  fileThumbnail: '',
                  fileUrl: storageUrl + fieldData.value,
                  fileExtension: fieldData.value.split('.').lastOrNull ?? '',
                );
                UiUtils.viewOrDownloadStudyMaterial(
                  context: context,
                  storeInExternalStorage: true,
                  studyMaterial: file,
                );
              },
              child: Text(
                UiUtils.getTranslatedLabel(context, viewFileKey),
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          if (fieldData.isCheckbox)
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: List.generate(fieldData.checkboxList.length, (index) {
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 2),
                  child: Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: Theme.of(context).colorScheme.secondary,
                        ),
                      ),
                      const SizedBox(width: 5),
                      Expanded(
                        child: Text(
                          fieldData.checkboxList[index].toCapitalized(),
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ),
          if (!fieldData.isFile && !fieldData.isCheckbox)
            Text(
              fieldData.value == 'null' ? '-' : fieldData.value,
              maxLines: 5,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Theme.of(context).colorScheme.secondary,
                fontWeight: FontWeight.w600,
              ),
            ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Align(
          alignment: AlignmentDirectional.centerStart,
          child: Text(
            UiUtils.getTranslatedLabel(context, otherDetailsKey),
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        const SizedBox(height: 6),
        for (int i = 0; i < dynamicFields.length; i += 2)
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _buildDynamicFieldDetailWidget(
                  fieldData: dynamicFields[i],
                  context: context,
                ),
              ),
              const SizedBox(width: 10),
              if (dynamicFields.length > i + 1)
                Expanded(
                  child: _buildDynamicFieldDetailWidget(
                    fieldData: dynamicFields[i + 1],
                    context: context,
                  ),
                )
              else
                const Spacer(),
            ],
          ),
      ],
    );
  }
}
