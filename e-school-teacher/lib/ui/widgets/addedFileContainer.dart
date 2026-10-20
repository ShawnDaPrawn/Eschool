import 'package:dotted_border/dotted_border.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

class AddedFileContainer extends StatelessWidget {
  const AddedFileContainer({
    required this.onDelete,
    required this.platformFile,
    super.key,
  });
  final PlatformFile platformFile;
  final Function onDelete;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return DottedBorder(
          options: RoundedRectDottedBorderOptions(
            dashPattern: const [10, 10],
            radius: const Radius.circular(10),
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.3),
          ),
          child: Padding(
            padding: const EdgeInsetsDirectional.only(start: 10),
            child: Row(
              children: [
                SizedBox(
                  width: boxConstraints.maxWidth * 0.75,
                  child: Text(
                    platformFile.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const Spacer(),
                IconButton(
                  onPressed: () {
                    onDelete();
                  },
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
