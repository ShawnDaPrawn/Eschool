import 'package:flutter/material.dart';

class ReportsTabContainer extends StatefulWidget {
  const ReportsTabContainer({
    required this.tabs,
    required this.onTapTab,
    required this.selectedTabIndex,
    super.key,
  });
  final List<String> tabs;
  final Function(int) onTapTab;
  final int selectedTabIndex;

  @override
  State<ReportsTabContainer> createState() => _ReportsTabContainerState();
}

class _ReportsTabContainerState extends State<ReportsTabContainer> {
  late final ScrollController _scrollController = ScrollController();

  @override
  void didUpdateWidget(ReportsTabContainer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.selectedTabIndex != widget.selectedTabIndex) {
      // Animate scroll based on the direction of tab change, clamped to bounds
      final targetOffset =
          _scrollController.offset +
          (widget.selectedTabIndex > oldWidget.selectedTabIndex ? 1 : -1) *
              MediaQuery.sizeOf(context).width *
              0.2;

      _scrollController.animateTo(
        targetOffset.clamp(0.0, _scrollController.position.maxScrollExtent),
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: ListView.builder(
        controller: _scrollController,
        itemBuilder: (context, index) {
          return GestureDetector(
            onTap: () {
              if (index == widget.selectedTabIndex) {
                return;
              }

              widget.onTapTab(index);
            },
            child: Container(
              margin: const EdgeInsetsDirectional.only(end: 20),
              decoration: BoxDecoration(
                color: widget.selectedTabIndex == index
                    ? Theme.of(context).colorScheme.primary
                    : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 15),
              alignment: Alignment.center,
              child: Text(
                widget.tabs[index],
                style: TextStyle(
                  color: widget.selectedTabIndex == index
                      ? Theme.of(context).colorScheme.onPrimary
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ),
          );
        },
        itemCount: widget.tabs.length,
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: 20),
      ),
    );
  }
}
