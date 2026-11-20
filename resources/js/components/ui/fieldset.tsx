import * as React from "react"

import { cn } from "@/lib/utils"

function Fieldset({ className, ...props }: React.ComponentProps<"fieldset">) {
  return (
    <fieldset
      data-slot="fieldset"
      className={cn(
        "rounded-lg border border-border px-6 pt-2 pb-6",
        className
      )}
      {...props}
    />
  )
}

function FieldsetLegend({ className, ...props }: React.ComponentProps<"legend">) {
  return (
    <legend
      data-slot="fieldset-legend"
      className={cn(
        "px-2 text-xs font-bold tracking-wide text-primary uppercase",
        className
      )}
      {...props}
    />
  )
}

export { Fieldset, FieldsetLegend }
